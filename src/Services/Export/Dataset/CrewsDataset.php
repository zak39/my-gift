<?php

namespace App\Services\Export\Dataset;

use App\Entity\Crew;
use App\Entity\User;
use App\Repository\CrewRepository;
use App\Services\Dto\ExportedDatasetDto;

/**
 * The crews the user belongs to, with their part in each and its members by name.
 */
final class CrewsDataset
{
    public function __construct(
        private readonly CrewRepository $crewRepository,
    ) {
    }

    public function of(User $user): ExportedDatasetDto
    {
        return ExportedDatasetDto::table('Groupes', ['Groupe', 'Rôle', 'Membre depuis', 'Créé le', 'Créé par vous', 'Membres'], array_map(
            fn (Crew $crew) => [
                'Groupe' => $crew->getName(),
                'Rôle' => $crew->getOwner() === $user ? 'Propriétaire' : 'Membre',
                'Membre depuis' => $crew->getJoinedAt($user),
                'Créé le' => $crew->getCreatedAt(),
                'Créé par vous' => $crew->getCreatedBy() === $user ? 'Oui' : 'Non',
                'Membres' => implode(', ', array_map(fn (User $member) => $member->getFullname(), $crew->getMembers())),
            ],
            $this->crewRepository->findForMember($user),
        ));
    }
}
