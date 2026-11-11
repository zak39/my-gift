<?php

namespace App\Services\Export\Dataset;

use App\Entity\User;
use App\Repository\FriendBookRepository;
use App\Services\Dto\ExportedDatasetDto;

/**
 * What the user told about themselves and what the app recorded of their account: one row.
 * Neither the password, a hash nobody can read back, nor the avatar, an image the profile
 * page shows. The end of their invitation link too, which they may have chosen.
 */
final class ProfileDataset
{
    public function __construct(
        private readonly FriendBookRepository $friendBookRepository,
    ) {
    }

    public function of(User $user): ExportedDatasetDto
    {
        return ExportedDatasetDto::table('Profil', ['Email', 'Pseudo', 'Prénom', 'Nom', 'Inscrit le', 'Email vérifié', 'Dernière activité', 'Fin du lien d\'invitation'], [[
            'Email' => $user->getEmail(),
            'Pseudo' => $user->getPseudo(),
            'Prénom' => $user->getFirstname(),
            'Nom' => $user->getLastname(),
            'Inscrit le' => $user->getCreatedAt(),
            'Email vérifié' => $user->isVerified() ? 'Oui' : 'Non',
            'Dernière activité' => $user->getLastActivityAt(),
            'Fin du lien d\'invitation' => $this->friendBookRepository->of($user)->getInvitationSlug(),
        ]]);
    }
}
