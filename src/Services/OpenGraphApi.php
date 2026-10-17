<?php

namespace App\Services;

use App\Services\Dto\OpenGraphDto;
use App\Services\Exception\OpenGraphFetchException;
use App\Services\Exception\OpenGraphNotFoundException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenGraphApi
{
    public function __construct(
        #[Autowire(service: 'app.public_http_client')]
        private HttpClientInterface $httpClient,
    ) {
    }

    public function getData(string $url): OpenGraphDto
    {
        $response = $this->httpClient->request('GET', $url, [
            'headers' => [
                'User-Agent' => 'Mozilla/5.0',
                'Accept' => 'text/html,application/xhtml+xml',
            ],
            'max_redirects' => 10,
            'timeout' => 10,
        ]);

        $statusCode = $response->getStatusCode();

        if ($statusCode >= 400) {
            throw new OpenGraphFetchException("HTTP request failed with status code $statusCode");
        }

        $html = $response->getContent();

        $openGraphData = $this->parseOpenGraph($html);

        return new OpenGraphDto($openGraphData);
    }

    private function parseOpenGraph(string $html): array
    {
        libxml_use_internal_errors(true);

        $dom = new \DOMDocument();

        $dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);

        /** @var \DOMElement[] $nodes */
        $nodes = $xpath->query('//meta[starts-with(@property, "og:")]');

        if (false === $nodes || 0 === count($nodes)) {
            throw new OpenGraphNotFoundException('No Open Graph meta tags found in the HTML content');
        }

        $openGraph = [];

        foreach ($nodes as $node) {
            $property = $node->getAttribute('property');
            $content = $node->getAttribute('content');

            if ($property && $content) {
                $openGraph[$property] = $content;
            }
        }

        return $openGraph;
    }
}
