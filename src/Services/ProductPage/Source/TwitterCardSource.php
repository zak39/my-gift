<?php

namespace App\Services\ProductPage\Source;

use App\Services\Dto\ProductPageDto;

/**
 * The Twitter Card tags (twitter:title…), which some pages give without Open Graph.
 */
final class TwitterCardSource implements ProductPageSource
{
    public function supports(\DOMXPath $page): bool
    {
        return $this->read($page)->hasData();
    }

    public function read(\DOMXPath $page): ProductPageDto
    {
        return new ProductPageDto(
            title: MetaTag::content($page, 'twitter:title'),
            description: MetaTag::content($page, 'twitter:description'),
            image: MetaTag::content($page, 'twitter:image', 'twitter:image:src'),
        );
    }
}
