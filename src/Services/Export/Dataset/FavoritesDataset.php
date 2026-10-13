<?php

namespace App\Services\Export\Dataset;

use App\Entity\Gift;
use App\Entity\User;
use App\Repository\GiftRepository;
use App\Services\Dto\ExportedDatasetDto;
use App\Services\Export\OwnerName;

/**
 * The gifts the user marked as favourite, those they may still see as on their favourites
 * page: a favourite on a list they are no longer a friend of tells nothing of it.
 */
final class FavoritesDataset
{
    public function __construct(
        private readonly GiftRepository $giftRepository,
    ) {
    }

    public function of(User $user): ExportedDatasetDto
    {
        return ExportedDatasetDto::table('Favoris', ['Cadeau', 'Propriétaire', 'Prix', 'Lien', 'Statut'], array_map(
            fn (Gift $gift) => [
                'Cadeau' => $gift->getTitle(),
                'Propriétaire' => OwnerName::of($gift, $user),
                'Prix' => $gift->getPrice(),
                'Lien' => $gift->getLink(),
                'Statut' => $gift->getStatus()?->label(),
            ],
            $this->giftRepository->findFavoritesGifts($user),
        ));
    }
}
