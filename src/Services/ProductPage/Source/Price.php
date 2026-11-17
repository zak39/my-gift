<?php

namespace App\Services\ProductPage\Source;

/**
 * A price as a page gives it (Open Graph's product:price:amount, JSON-LD's offers.price),
 * read as a gift's price, which is in euros.
 */
final class Price
{
    /**
     * The amount in euros, or null when it is not one the gift can take.
     *
     * @param mixed $amount   "49.99", "49,99", 49.99 or 49 (JSON-LD gives a number or a string), or null
     * @param mixed $currency an ISO 4217 code ("EUR"): a page that gives none gives no price
     *
     * @return float|null a number (49.99) rounded to the cent, not a text to show: Form.php
     *                    writes it as the field shows it (49,99)
     */
    public static function parse(mixed $amount, mixed $currency): ?float
    {
        if (!\is_string($currency) || 'EUR' !== strtoupper(trim($currency))) {
            return null;
        }

        if (\is_string($amount)) {
            // Spaces group thousands in French: normal, non-breaking, narrow non-breaking
            $amount = str_replace([' ', "\u{a0}", "\u{202f}"], '', $amount);

            // "1.299,00": with a comma, it is the decimal separator and the dots group thousands
            if (str_contains($amount, ',')) {
                $amount = str_replace(['.', ','], ['', '.'], $amount);
            }
        }

        if (!is_numeric($amount) || $amount <= 0) {
            return null;
        }

        return round((float) $amount, 2);
    }
}
