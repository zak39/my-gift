<?php

namespace App\Event;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * An inactive account is told it will be deleted: first warning, or reminder before the date.
 * The date is the one scheduled on the user, so the warning and the reminder cannot disagree.
 */
final class AccountInactivityNoticeEvent extends Event
{
    public function __construct(
        private readonly User $user,
        private readonly bool $reminder,
    ) {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getDeletionDate(): \DateTimeImmutable
    {
        return $this->user->getScheduledDeletionAt() ?? throw new \LogicException(\sprintf('User %d has no scheduled deletion to be told of.', $this->user->getId()));
    }

    public function isReminder(): bool
    {
        return $this->reminder;
    }
}
