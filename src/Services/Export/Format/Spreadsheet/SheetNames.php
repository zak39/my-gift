<?php

namespace App\Services\Export\Format\Spreadsheet;

/**
 * The names of a workbook's sheets, made valid from the lists' names: a crew's name is
 * free text, a sheet's is not. One instance per workbook, as names are unique within it.
 *
 * What OpenSpout refuses, throwing: more than 31 characters, any of \ / ? * : [ ],
 * a quote at the start or the end, a name already taken. And what it lets through but
 * Excel does not: 31 characters counted as Excel counts them, see cut().
 */
final class SheetNames
{
    private const MAX_LENGTH = 31;

    /**
     * @var list<string> the names given so far in this workbook, lowercased: Excel tells
     *                   "Famille" from "famille" no more than it lets both in one workbook
     */
    private array $claimed = [];

    /**
     * A valid name for the list's sheet, as close to $name as possible, and not given yet:
     * "Groupe Famille", then "Groupe Famille (2)" for a second crew of that name.
     */
    public function claim(string $name): string
    {
        // Cleaned before cut, so that the removed characters leave room for the others.
        $name = str_replace(['\\', '/', '?', '*', ':', '[', ']'], '', $name);
        $base = trim($this->cut($name, self::MAX_LENGTH), " '");

        $name = $base;
        for ($i = 2; \in_array(mb_strtolower($name), $this->claimed, true); ++$i) {
            $suffix = " ($i)";
            // Cut before the suffix, which counts in the 31 characters too.
            $name = rtrim($this->cut($base, self::MAX_LENGTH - \strlen($suffix)), " '").$suffix;
        }

        $this->claimed[] = mb_strtolower($name);

        return $name;
    }

    /**
     * $name, cut to $length as Excel counts: in UTF-16 units, where an emoji takes two.
     * Cut by characters, never bytes, as an "é" cut in two is no longer UTF-8.
     */
    private function cut(string $name, int $length): string
    {
        $name = mb_substr($name, 0, $length);
        while (\strlen(mb_convert_encoding($name, 'UTF-16LE', 'UTF-8')) / 2 > $length) {
            $name = mb_substr($name, 0, -1);
        }

        return $name;
    }
}
