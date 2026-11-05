<?php

namespace App\Services\Export\Dataset;

use App\Entity\User;
use App\Repository\FriendBookRepository;
use App\Services\Dto\ExportedDatasetDto;

/**
 * The users the user blocked, by name: their email is theirs.
 */
final class BlockedUsersDataset
{
    public function __construct(
        private readonly FriendBookRepository $friendBookRepository,
    ) {
    }

    public function of(User $user): ExportedDatasetDto
    {
        // The pseudo only: a blocked user is no friend, whose full name stays theirs.
        return ExportedDatasetDto::table('Bloqués', ['Pseudo'], array_map(
            fn (User $blocked) => ['Pseudo' => $blocked->getPseudo()],
            $this->friendBookRepository->of($user)->getBlockedUsers()->getValues(),
        ));
    }
}
