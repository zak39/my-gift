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

    public function __invoke(Gift $gift): void
    {
        // The price as loaded from the database, before the form wrote the submitted one:
        // read here rather than by every caller before handleRequest().
        $previousPrice = (float) $this->entityManager->getUnitOfWork()->getOriginalEntityData($gift)['price'];

        $this->entityManager->flush();

        if ($previousPrice !== $gift->getPrice()) {
            $this->giftLifecycle->priceChanged($gift, $previousPrice);
        }
    }
}
