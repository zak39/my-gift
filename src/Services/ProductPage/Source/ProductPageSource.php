<?php

namespace App\Services\ProductPage\Source;

use App\Services\Dto\ProductPageDto;

/**
 * One way a page describes its product (Open Graph, JSON-LD, Twitter Card, plain HTML).
 * ProductPageReader asks them in turn and reads the page with the first that supports it,
 * alone.
 */
interface ProductPageSource
{
    /**
     * Whether the page describes its product this way: then this source alone reads it.
     */
    public function supports(\DOMXPath $page): bool;

    /**
     * What this source finds on a page it supports, never an exception.
     */
    public function read(\DOMXPath $page): ProductPageDto;
}
