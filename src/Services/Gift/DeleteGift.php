<?php

namespace App\Services\Gift;

use App\Entity\Gift;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Deletes a gift from its owners' list, theirs or their crew's.
 */
final class DeleteGift
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(Gift $gift): void
    {
        $this->entityManager->remove($gift);
        $this->entityManager->flush();
    }
}
