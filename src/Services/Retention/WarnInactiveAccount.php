<?php

namespace App\Services\Retention;

use App\Entity\User;
use App\Event\AccountInactivityNoticeEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Tells an inactive account the date it will go. The date is saved with its email queued
 * (Messenger's doctrine transport), or neither: a date saved without its email would let
 * the account go unwarned.
 */
class WarnInactiveAccount
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventDispatcherInterface $eventDispatcher,
        private AccountRetention $retention,
    ) {
    }

    public function __invoke(User $user): void
    {
        $this->entityManager->wrapInTransaction(function () use ($user): void {
            $user->scheduleDeletion($this->retention->deletionDateIfWarnedNow());
            $this->eventDispatcher->dispatch(new AccountInactivityNoticeEvent($user, reminder: false));
        });
    }
}
