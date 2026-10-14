<?php

namespace App\Services\Export\Dataset;

use App\Entity\User;
use App\Services\Dto\ExportedDatasetDto;

/**
 * What the user told about themselves and what the app recorded of their account: one row.
 * Neither the password, a hash nobody can read back, nor the avatar, an image the profile
 * page shows.
 */
final class ProfileDataset
{
    public function of(User $user): ExportedDatasetDto
    {
        return ExportedDatasetDto::table('Profil', ['Email', 'Prénom', 'Nom', 'Inscrit le', 'Email vérifié', 'Dernière activité'], [[
            'Email' => $user->getEmail(),
            'Prénom' => $user->getFirstname(),
            'Nom' => $user->getLastname(),
            'Inscrit le' => $user->getCreatedAt(),
            'Email vérifié' => $user->isVerified() ? 'Oui' : 'Non',
            'Dernière activité' => $user->getLastActivityAt(),
        ]]);
    }
}
