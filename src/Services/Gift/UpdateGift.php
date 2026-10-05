<?php

namespace App\Services\Gift;

use App\Entity\Gift;
use App\Services\Lifecycle\GiftLifecycle;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Saves a gift its owner edited, and tells its participants when its price changed.
 */
final class UpdateGift
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GiftLifecycle $giftLifecycle,
    ) {
    }

    /**
     * @param float|null $previousPrice read before the form wrote the submitted one
     */
    public function __invoke(Gift $gift, ?float $previousPrice): void
    {
        $this->entityManager->flush();

        if ($previousPrice !== $gift->getPrice()) {
            $this->giftLifecycle->priceChanged($gift, $previousPrice);
        }
    }
}
