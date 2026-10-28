<?php

namespace App\Services\Gift;

use App\Entity\Gift;
use App\Entity\User;
use App\Event\GiftDeletedEvent;
use App\Services\Exception\GiftActionRefusedException;
use App\Services\Lifecycle\GiftLifecycle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Deletes a gift from its owners' list, theirs or their crew's, its givers told.
 *
 * Under the gift's lock, as every transition (GiftLifecycle::whileLocked()): a friend
 * reserving or chipping in meanwhile is read again with the other participants, so told and
 * removed with them, rather than left untold or breaking the delete on participant.gift_id.
 * Their email is queued in the same transaction, and goes only if the gift does: as long as
 * Messenger's transport is Doctrine's, on the same connection (MESSENGER_TRANSPORT_DSN). A
 * redis or amqp one would publish it at once, before the commit.
 */
final class DeleteGift
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GiftLifecycle $giftLifecycle,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @param ?User $deletedBy whoever deleted it from the site, null from the admin: its
     *                         givers' email then names My Gift's team, not the admin
     *
     * @throws GiftActionRefusedException the gift was deleted meanwhile, by another member of its crew
     */
    public function __invoke(Gift $gift, ?User $deletedBy): void
    {
        $this->giftLifecycle->whileLocked($gift, function () use ($gift, $deletedBy): void {
            $this->eventDispatcher->dispatch(new GiftDeletedEvent($gift, $deletedBy));
            $this->entityManager->remove($gift);
            $this->entityManager->flush();
        });
    }
}
