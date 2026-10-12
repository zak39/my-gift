<?php

namespace App\Scheduler;

use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * The "default" schedule. Its tasks are declared on their own class (#[AsPeriodicTask],
 * #[AsCronTask]), e.g. App\Command\UserPurgeInactiveCommand, and run in the worker
 * consuming the scheduler_default transport (castor sf:ca).
 */
#[AsSchedule]
class DefaultSchedule implements ScheduleProviderInterface
{
    public function __construct(
        private CacheInterface $cache,
    ) {
    }

    public function getSchedule(): Schedule
    {
        return (new Schedule())
            // A run missed while the worker was down happens once on its restart.
            ->stateful($this->cache)
            ->processOnlyLastMissedRun(true)
        ;
    }
}
