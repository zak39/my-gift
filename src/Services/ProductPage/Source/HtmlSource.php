<?php

namespace App\Services\ProductPage\Source;

use App\Services\Dto\ProductPageDto;

/**
 * The page's own <title> and <meta name="description">: the last resort, which almost any
 * page has, but often with the shop's name around the product's.
 */
final class HtmlSource implements ProductPageSource
{
    public function supports(\DOMXPath $page): bool
    {
        return $this->read($page)->hasData();
    }

    public function read(\DOMXPath $page): ProductPageDto
    {
        return new ProductPageDto(
            // In <head> only: an inline SVG carries its own <title>
            title: $page->query('/html/head/title')?->item(0)?->textContent,
            description: MetaTag::content($page, 'description'),
        );
    }
}
