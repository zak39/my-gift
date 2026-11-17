<?php

namespace App\Services\ProductPage\Source;

use App\Services\Dto\ProductPageDto;

/**
 * The Open Graph tags (og:title…), and the price of the `product` type
 * (product:price:amount), which some shops use instead of JSON-LD.
 */
final class OpenGraphSource implements ProductPageSource
{
    public function supports(\DOMXPath $page): bool
    {
        return null !== MetaTag::content($page, 'og:title', 'og:description');
    }

    public function read(\DOMXPath $page): ProductPageDto
    {
        return new ProductPageDto(
            title: MetaTag::content($page, 'og:title'),
            description: MetaTag::content($page, 'og:description'),
            image: MetaTag::content($page, 'og:image:secure_url', 'og:image', 'og:image:url'),
            price: Price::parse(
                MetaTag::content($page, 'product:price:amount', 'og:price:amount'),
                MetaTag::content($page, 'product:price:currency', 'og:price:currency'),
            ),
        );
    }
}
