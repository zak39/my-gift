<?php

namespace App\Services\Retention;

use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * How long an account is kept without activity (GDPR storage limitation): the durations
 * live in config/services.yaml, the dates they give live here.
 *
 * The deletion date is fixed when warning and stored (User::$scheduledDeletionAt): the date
 * promised in the email holds whatever happens later, a short month, a changed notice or a
 * purge that did not run for a while. Any activity cancels it (User::recordActivity()).
 */
class AccountRetention
{
    private \DateInterval $warnAfter;
    private \DateInterval $notice;
    private \DateInterval $remindBefore;
    private \DateInterval $unverifiedAfter;

    public function __construct(
        private ClockInterface $clock,
        #[Autowire('%app.retention.warn_after%')]
        string $warnAfter,
        #[Autowire('%app.retention.notice%')]
        string $notice,
        #[Autowire('%app.retention.remind_before%')]
        string $remindBefore,
        #[Autowire('%app.retention.unverified_after%')]
        string $unverifiedAfter,
    ) {
        $this->warnAfter = new \DateInterval($warnAfter);
        $this->notice = new \DateInterval($notice);
        $this->remindBefore = new \DateInterval($remindBefore);
        $this->unverifiedAfter = new \DateInterval($unverifiedAfter);
    }

    /** Accounts with no activity since then get warned. */
    public function warnIfInactiveSince(): \DateTimeImmutable
    {
        return $this->clock->now()->sub($this->warnAfter);
    }

    /** The date to store and print when warning now: midnight, so that day's run deletes it. */
    public function deletionDateIfWarnedNow(): \DateTimeImmutable
    {
        return $this->clock->now()->add($this->notice)->setTime(0, 0);
    }

    /** Accounts scheduled for deletion before then get their reminder. */
    public function remindIfScheduledBefore(): \DateTimeImmutable
    {
        return $this->clock->now()->add($this->remindBefore);
    }

    /** Accounts scheduled for deletion before then are deleted. */
    public function deleteIfScheduledBefore(): \DateTimeImmutable
    {
        return $this->clock->now();
    }

    /** Accounts created before then and still unverified are deleted, unwarned. */
    public function deleteIfUnverifiedSince(): \DateTimeImmutable
    {
        return $this->clock->now()->sub($this->unverifiedAfter);
    }
}
