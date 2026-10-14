<?php

namespace App\Services\Export\Dataset;

use App\Entity\Participant;
use App\Entity\User;
use App\Repository\ParticipantRepository;
use App\Services\Dto\ExportedDatasetDto;
use App\Services\Export\OwnerName;

/**
 * The gifts the user gives or gave to others: their reservations and contributions, on
 * gifts they may still open even once no friend of the owner (GiftVisibilityVoter). Their
 * own amount only, never another contributor's.
 */
final class ParticipationsDataset
{
    public function __construct(
        private readonly ParticipantRepository $participantRepository,
    ) {
    }

    public function of(User $user): ExportedDatasetDto
    {
        return ExportedDatasetDto::table('Participations', ['Cadeau', 'Propriétaire', 'Type', 'Montant', 'Affiché comme', 'Statut du cadeau'], array_map(
            fn (Participant $participant) => [
                'Cadeau' => $participant->getGift()->getTitle(),
                'Propriétaire' => OwnerName::of($participant->getGift(), $user),
                'Type' => $participant->getStatus()?->label(),
                'Montant' => $participant->getAmount(),
                // Their pseudonym when anonymous: what the gift's owners read.
                'Affiché comme' => $participant->getDisplayName(),
                'Statut du cadeau' => $participant->getGift()->getStatus()?->label(),
            ],
            $this->participantRepository->findOfUser($user),
        ));
    }
}
