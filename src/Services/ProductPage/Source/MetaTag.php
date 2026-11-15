<?php

namespace App\Services\ProductPage\Source;

/**
 * The content of a page's <meta> tag. Open Graph names its tags in `property` and Twitter
 * Card in `name`, but many pages mix them up: both attributes are read.
 */
final class MetaTag
{
    /**
     * The content of the first tag named one of $names, in that order, or null.
     *
     * @param string ...$names fixed tag names (og:title…), never user input: they are put
     *                         in the XPath query as is
     */
    public static function content(\DOMXPath $page, string ...$names): ?string
    {
        foreach ($names as $name) {
            $nodes = $page->query(\sprintf('//meta[@property="%1$s" or @name="%1$s"]/@content', $name));

            foreach ($nodes ?: [] as $node) {
                $content = trim($node->nodeValue ?? '');

                if ('' !== $content) {
                    return $content;
                }
            }
        }

        return null;
    }
}
