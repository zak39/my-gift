<?php

namespace App\Services\User;

use App\Entity\User;
use App\Repository\UserRepository;

/**
 * Free pseudos to suggest instead of $pseudo, taken: the same one followed by a number,
 * jean1, jean2, jean3 for jean.
 */
final class SuggestPseudo
{
    /**
     * The taken ones are read on what pick() may keep of $pseudo: it cuts a long one to make
     * room for the number. Up to 4 digits, so up to 9999 tries, are covered.
     */
    private const MAX_DIGITS = 4;

    private const COUNT = 3;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly PseudoPicker $pseudoPicker,
    ) {
    }

    /**
     * @return list<string> self::COUNT pseudos, all different: each one picked is taken for the next
     */
    public function __invoke(string $pseudo): array
    {
        $prefix = substr($pseudo, 0, User::PSEUDO_MAX_LENGTH - self::MAX_DIGITS);
        $taken = $this->userRepository->findPseudosStartingWith($prefix);
        $suggestions = [];

        for ($i = 0; $i < self::COUNT; ++$i) {
            $suggestions[] = $suggestion = $this->pseudoPicker->pick($pseudo, $taken);
            $taken[] = strtolower($suggestion);
        }

        return $suggestions;
    }
}
