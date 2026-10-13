<?php

namespace App\Services\Export\Dataset;

use App\Entity\User;
use App\Repository\FriendBookRepository;
use App\Services\Dto\ExportedDatasetDto;

/**
 * The user's friends, by name: their email is theirs.
 */
final class FriendsDataset
{
    public function __construct(
        private readonly FriendBookRepository $friendBookRepository,
    ) {
    }

    public function of(User $user): ExportedDatasetDto
    {
        return ExportedDatasetDto::table('Amis', ['Prénom', 'Nom'], array_map(
            fn (User $friend) => ['Prénom' => $friend->getFirstname(), 'Nom' => $friend->getLastname()],
            $this->friendBookRepository->of($user)->getFriends()->getValues(),
        ));
    }
}
