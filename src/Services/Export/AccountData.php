<?php

namespace App\Services\Export;

use App\Entity\User;
use App\Services\Dto\ExportedDatasetDto;
use App\Services\Export\Dataset\BlockedUsersDataset;
use App\Services\Export\Dataset\CrewsDataset;
use App\Services\Export\Dataset\FavoritesDataset;
use App\Services\Export\Dataset\FriendsDataset;
use App\Services\Export\Dataset\GiftsDataset;
use App\Services\Export\Dataset\ParticipationsDataset;
use App\Services\Export\Dataset\ProfileDataset;
use App\Services\Export\Dataset\SectionsDataset;

/**
 * The user's own data the export gives them (GDPR, rights of access and portability):
 * their profile and what they made or joined in the app, not the friend requests and crew
 * invitations still pending, which the app shows. What every format writes. One dataset
 * per kind of data, under Dataset/, listed here in the order of the file; a new one needs
 * nothing else.
 *
 * Other users appear by name only, never by email.
 */
final class AccountData
{
    public function __construct(
        private readonly ProfileDataset $profile,
        private readonly SectionsDataset $sections,
        private readonly GiftsDataset $gifts,
        private readonly ParticipationsDataset $participations,
        private readonly FavoritesDataset $favorites,
        private readonly FriendsDataset $friends,
        private readonly BlockedUsersDataset $blockedUsers,
        private readonly CrewsDataset $crews,
    ) {
    }

    /**
     * @return list<ExportedDatasetDto>
     */
    public function of(User $user): array
    {
        return [
            $this->profile->of($user),
            $this->sections->of($user),
            $this->gifts->of($user),
            $this->participations->of($user),
            $this->favorites->of($user),
            $this->friends->of($user),
            $this->blockedUsers->of($user),
            $this->crews->of($user),
        ];
    }
}
