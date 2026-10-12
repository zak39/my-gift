<?php

namespace App\Services\Retention;

use App\Entity\User;
use App\Event\AccountInactivityNoticeEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Reminds a warned account, days before it goes, of the date given in its warning. Saved
 * with its email queued, as WarnInactiveAccount does.
 */
class RemindInactiveAccount
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventDispatcherInterface $eventDispatcher,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(User $user): void
    {
        $this->entityManager->wrapInTransaction(function () use ($user): void {
            $user->markInactivityReminded($this->clock->now());
            $this->eventDispatcher->dispatch(new AccountInactivityNoticeEvent($user, reminder: true));
        });
    }
}
