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
        return ExportedDatasetDto::table('Bloqués', ['Prénom', 'Nom'], array_map(
            fn (User $blocked) => ['Prénom' => $blocked->getFirstname(), 'Nom' => $blocked->getLastname()],
            $this->friendBookRepository->of($user)->getBlockedUsers()->getValues(),
        ));
    }
}
