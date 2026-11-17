<?php

namespace App\Services\ProductPage\Source;

use App\Services\Dto\ProductPageDto;

/**
 * The schema.org Product a page describes in JSON-LD (<script type="application/ld+json">),
 * which most shops publish for search engines: often a cleaner name than og:title, and the
 * price. Only the first Product is read: a page also lists the related products after it.
 */
final class JsonLdSource implements ProductPageSource
{
    /**
     * A Product with something to read: a review widget's one, holding only its rating,
     * leaves the page to the next sources.
     */
    public function supports(\DOMXPath $page): bool
    {
        return $this->read($page)->hasData();
    }

    public function read(\DOMXPath $page): ProductPageDto
    {
        return $this->toDto($this->product($page) ?? []);
    }

    /**
     * The first Product of the page's JSON-LD scripts, or null.
     */
    private function product(\DOMXPath $page): ?array
    {
        foreach ($page->query('//script[@type="application/ld+json"]') ?: [] as $script) {
            $data = json_decode($script->textContent, true);

            $product = \is_array($data) ? $this->findProduct($data) : null;

            if (null !== $product) {
                return $product;
            }
        }

        return null;
    }

    /**
     * The first Product in $data: a script holds one object, a list of them, or a @graph of
     * the page's objects, and a WebPage may give it as its mainEntity; a ProductGroup (a
     * product in several sizes or colours) is read as its product.
     */
    private function findProduct(array $data): ?array
    {
        if (array_is_list($data)) {
            foreach ($data as $item) {
                $product = \is_array($item) ? $this->findProduct($item) : null;

                if (null !== $product) {
                    return $product;
                }
            }

            return null;
        }

        $types = array_map(
            // "Product", "schema:Product" or "https://schema.org/Product"
            static fn (mixed $type): string => \is_string($type) ? preg_replace('~^.*[/:]~', '', $type) : '',
            (array) ($data['@type'] ?? []),
        );

        if (array_intersect($types, ['Product', 'ProductGroup'])) {
            return $data;
        }

        foreach (['@graph', 'mainEntity'] as $key) {
            $product = \is_array($data[$key] ?? null) ? $this->findProduct($data[$key]) : null;

            if (null !== $product) {
                return $product;
            }
        }

        return null;
    }

    private function toDto(array $product): ProductPageDto
    {
        // A list of offers (one per seller or variant) gives its first; an AggregateOffer its lowest price
        $offer = self::first($product['offers'] ?? null);

        // A ProductGroup gives its offers on its variants
        if ([] === $offer) {
            $offer = self::first(self::first($product['hasVariant'] ?? null)['offers'] ?? null);
        }

        $priceSpecification = self::first($offer['priceSpecification'] ?? null);

        return new ProductPageDto(
            title: $this->text($product['name'] ?? null),
            description: $this->text($product['description'] ?? null),
            image: $this->image($product['image'] ?? null),
            price: Price::parse(
                $offer['price'] ?? $offer['lowPrice'] ?? $priceSpecification['price'] ?? null,
                $offer['priceCurrency'] ?? $priceSpecification['priceCurrency'] ?? null,
            ),
        );
    }

    /**
     * An object, or the first of a list of them: [] when there is none.
     */
    private static function first(mixed $value): array
    {
        if (\is_array($value) && array_is_list($value)) {
            $value = $value[0] ?? null;
        }

        return \is_array($value) ? $value : [];
    }

    /**
     * Text as the page shows it: some shops put HTML and entities in their JSON-LD. A line
     * break or a block's edge becomes a new line, or strip_tags() would glue the words on
     * either side ("rouge<br>Taille" as "rougeTaille").
     */
    private function text(mixed $value): ?string
    {
        if (!\is_string($value)) {
            return null;
        }

        $value = preg_replace('~<br\s*/?>|</?(?:p|li|div|h[1-6])\b[^>]*>~i', "\n", $value) ?? $value;

        return html_entity_decode(strip_tags($value), \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
    }

    /**
     * The URL of an image given as a string, an ImageObject, or a list of either: the first.
     */
    private function image(mixed $image): ?string
    {
        if (\is_array($image) && array_is_list($image)) {
            $image = $image[0] ?? null;
        }

        if (\is_array($image)) {
            $image = $image['url'] ?? $image['contentUrl'] ?? null;
        }

        return \is_string($image) ? $image : null;
    }
}
