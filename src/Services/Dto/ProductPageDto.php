<?php

namespace App\Services\Dto;

/**
 * What a product page tells about its product, to prefill a gift: read by ProductPageReader
 * with the first source the page supports. A field the page does not give is null, never an
 * empty string.
 */
final class ProductPageDto
{
    /** The longest title or description a Gift takes (Assert\Length on both) */
    public const MAX_LENGTH = 255;

    public readonly ?string $title;
    public readonly ?string $description;
    public readonly ?string $image;

    public function __construct(
        ?string $title = null,
        ?string $description = null,
        ?string $image = null,
        /** In euros: a price in another currency is not taken */
        public readonly ?float $price = null,
    ) {
        // A title fits on one line; a description keeps its line breaks. Both cut to what a
        // Gift takes: a JSON-LD description is often the whole product text, which the
        // form would refuse
        $this->title = self::shorten(self::clean(null === $title ? null : preg_replace('/\s+/u', ' ', $title) ?? $title));
        $this->description = self::shorten(self::clean($description));
        $this->image = self::clean($image);
    }

    public function withImage(?string $image): self
    {
        return new self($this->title, $this->description, $image, $this->price);
    }

    public function hasData(): bool
    {
        return null !== $this->title
            || null !== $this->description
            || null !== $this->image
            || null !== $this->price
        ;
    }

    private static function clean(?string $text): ?string
    {
        $text = null === $text ? '' : trim($text);

        return '' === $text ? null : $text;
    }

    /**
     * $text cut to MAX_LENGTH characters, an ellipsis telling it goes on.
     */
    private static function shorten(?string $text): ?string
    {
        if (null === $text || mb_strlen($text) <= self::MAX_LENGTH) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, self::MAX_LENGTH - 1)).'…';
    }
}
