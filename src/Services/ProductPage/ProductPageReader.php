<?php

namespace App\Services\ProductPage;

use App\Services\Dto\ProductPageDto;
use App\Services\Exception\ProductPageFetchException;
use App\Services\Exception\ProductPageNotFoundException;
use App\Services\ProductPage\Source\HtmlSource;
use App\Services\ProductPage\Source\JsonLdSource;
use App\Services\ProductPage\Source\OpenGraphSource;
use App\Services\ProductPage\Source\ProductPageSource;
use App\Services\ProductPage\Source\TwitterCardSource;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DomCrawler\UriResolver;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpClientException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Reads the product a pasted link points at, to prefill a gift: with the first of $sources
 * the page supports, alone.
 */
final class ProductPageReader
{
    /**
     * Headers a browser sends: some shops turn away a request that does not look like one.
     * It gets past no real anti-bot protection (Cloudflare, Datadome…).
     */
    private const HEADERS = [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language' => 'fr-FR,fr;q=0.9',
    ];

    /** A product page's HTML weighs a few hundred KB, rarely more than 2 MB */
    private const MAX_BYTES = 5 * 1024 * 1024;

    /** @var list<ProductPageSource> */
    private array $sources;

    public function __construct(
        #[Autowire(service: 'app.public_http_client')]
        private HttpClientInterface $httpClient,
        OpenGraphSource $openGraph,
        JsonLdSource $jsonLd,
        TwitterCardSource $twitterCard,
        HtmlSource $html,
    ) {
        // Open Graph first, as the prefill always read it; the page's <title> last, which
        // almost any page has, with the shop's name often around the product's
        $this->sources = [$openGraph, $jsonLd, $twitterCard, $html];
    }

    /**
     * @throws ProductPageFetchException    the page cannot be had (error status, timeout…)
     * @throws ProductPageNotFoundException the page gives nothing to prefill a gift with
     */
    public function read(string $url): ProductPageDto
    {
        try {
            // A malformed URL or a private address throws here, an error status on getContent()
            $response = $this->httpClient->request('GET', $url, [
                'headers' => self::HEADERS,
                'max_redirects' => 10,
                'timeout' => 10,
                // timeout is only an idle one: a server sending a byte now and then, or an
                // endless body, would keep the worker as long as it likes
                'max_duration' => 10,
                // Thrown in the client, caught below as a transport error
                'on_progress' => static function (int $downloaded): void {
                    if ($downloaded > self::MAX_BYTES) {
                        throw new \LengthException(sprintf('The page is over %d bytes.', self::MAX_BYTES));
                    }
                },
            ]);

            $html = $response->getContent();
        } catch (HttpClientException|\InvalidArgumentException $e) {
            throw new ProductPageFetchException($e->getMessage(), previous: $e);
        }

        $page = $this->load($html, $this->charset($response, $html));

        $product = $this->sourceFor($page)?->read($page);

        if (null === $product || !$product->hasData()) {
            throw new ProductPageNotFoundException('No product data found in the HTML content');
        }

        // A relative image (/img/p.jpg) is relative to the page it ends up on, after redirects
        if (null !== $product->image) {
            $product = $product->withImage(UriResolver::resolve($product->image, $response->getInfo('url')));
        }

        return $product;
    }

    private function sourceFor(\DOMXPath $page): ?ProductPageSource
    {
        foreach ($this->sources as $source) {
            if ($source->supports($page)) {
                return $source;
            }
        }

        return null;
    }

    /**
     * The page's charset: from its Content-Type header, else its <meta charset>, else UTF-8.
     */
    private function charset(ResponseInterface $response, string $html): string
    {
        $contentType = $response->getHeaders(false)['content-type'][0] ?? '';

        if (preg_match('~charset=["\']?([\w-]+)~i', $contentType, $match)
            || preg_match('~<meta[^>]+charset=["\']?([\w-]+)~i', $html, $match)
        ) {
            return $match[1];
        }

        return 'UTF-8';
    }

    private function load(string $html, string $charset): \DOMXPath
    {
        $dom = new \DOMDocument();

        // DOMDocument::loadHTML('') throws a ValueError: an empty page is one no source supports
        if ('' === trim($html)) {
            return new \DOMXPath($dom);
        }

        if ('UTF-8' !== strtoupper($charset)) {
            try {
                $html = mb_convert_encoding($html, 'UTF-8', $charset);
            } catch (\ValueError) {
                // A charset PHP does not know: the page is read as UTF-8
            }
        }

        $previous = libxml_use_internal_errors(true);

        // Or libxml reads the page as ISO-8859-1 until its <meta charset>, often after the <title>
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new \DOMXPath($dom);
    }
}
