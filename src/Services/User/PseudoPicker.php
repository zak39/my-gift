<?php

namespace App\Services\User;

use App\Entity\User;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Builds a free pseudo from a base and the pseudos already taken: no database here, so
 * that the migration giving the existing users their pseudo uses it too (SuggestPseudo
 * brings the taken ones otherwise).
 *
 * Used by the migration Version20261104215548: keep this class, its namespace, pick() and
 * baseFrom(), or a fresh install, which plays every migration, breaks there.
 */
final class PseudoPicker
{
    /**
     * A base made from free text, a first name: ASCII ("Hélène" gives "helene"), and only
     * the characters a pseudo allows. Never empty: a first name of emoji alone gives "membre".
     */
    public function baseFrom(string $text): string
    {
        $base = (new AsciiSlugger())->slug($text)->lower()->toString();

        return '' === $base ? 'membre' : $base;
    }

    /**
     * A pseudo made of $base and a number, valid (User's length and characters) and free:
     * not in $taken, whatever the case. The first free number wins:
     *
     *     pick('jean', [])                  // 'jean1'
     *     pick('Jean', ['jean1', 'jean2'])  // 'Jean3': "Jean1" is "jean1", taken
     *     pick('jean42', ['jean42'])        // 'jean421': a pseudo typed with a number gets one more
     *     pick('a', [])                     // 'a10': "a1" to "a9" are too short (3 characters minimum)
     *     pick(str_repeat('x', 30), [])     // 29 "x" then '1': the base is cut to leave the number room
     *
     * @param list<string> $taken the pseudos already taken, in lowercase
     */
    public function pick(string $base, array $taken): string
    {
        for ($i = 1;; ++$i) {
            $number = (string) $i;
            $pseudo = substr($base, 0, User::PSEUDO_MAX_LENGTH - \strlen($number)).$number;

            if (\strlen($pseudo) >= User::PSEUDO_MIN_LENGTH && !\in_array(strtolower($pseudo), $taken, true)) {
                return $pseudo;
            }
        }
    }
}
