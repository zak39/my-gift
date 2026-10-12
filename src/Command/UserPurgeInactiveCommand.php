<?php

namespace App\Command;

use App\Entity\User;
use App\Enum\AccountDeletionReason;
use App\Repository\UserRepository;
use App\Services\Lifecycle\UserLifecycle;
use App\Services\Retention\AccountRetention;
use App\Services\Retention\RemindInactiveAccount;
use App\Services\Retention\WarnInactiveAccount;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;

/**
 * The GDPR storage limitation, run every night by the scheduler (App\Scheduler\DefaultSchedule;
 * the worker must consume its transport: messenger:consume async scheduler_default).
 * The durations are in config/services.yaml, the dates in AccountRetention.
 */
#[AsCommand(
    name: 'app:users:purge-inactive',
    description: 'Warns the inactive accounts, then deletes them once their notice ran out',
)]
#[AsPeriodicTask(frequency: '1 day', from: '04:00')]
class UserPurgeInactiveCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private UserLifecycle $userLifecycle,
        private AccountRetention $retention,
        private WarnInactiveAccount $warnInactiveAccount,
        private RemindInactiveAccount $remindInactiveAccount,
        private EntityManagerInterface $entityManager,
        private ManagerRegistry $doctrine,
        private LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Option(description: 'List who would be warned or deleted, without sending or deleting anything')]
        bool $dryRun = false,
    ): int {
        if ($dryRun) {
            $io->note('Dry run: nothing is sent nor deleted.');
        }

        $unverifiedSince = $this->retention->deleteIfUnverifiedSince();
        $failed = $this->apply($io, $dryRun, 'Unverified accounts to delete',
            fn (array $exceptIds, ?int $limit) => $this->userRepository->findUnverifiedInactiveSince($unverifiedSince, $exceptIds, $limit),
            fn (User $user) => $this->userLifecycle->delete($user, AccountDeletionReason::Unverified),
        );

        $deleteBefore = $this->retention->deleteIfScheduledBefore();
        $failed = [...$failed, ...$this->apply($io, $dryRun, 'Inactive accounts to delete',
            fn (array $exceptIds, ?int $limit) => $this->userRepository->findToDeleteForInactivity($deleteBefore, $exceptIds, $limit),
            fn (User $user) => $this->userLifecycle->delete($user, AccountDeletionReason::Inactivity),
        )];

        // Short of the deletion date: an account whose notice ran out gets no reminder.
        $remindBefore = $this->retention->remindIfScheduledBefore();
        $failed = [...$failed, ...$this->apply($io, $dryRun, 'Inactive accounts to remind',
            fn (array $exceptIds, ?int $limit) => $this->userRepository->findToRemindOfInactivity($remindBefore, $deleteBefore, $exceptIds, $limit),
            $this->remindInactiveAccount,
        )];

        $inactiveSince = $this->retention->warnIfInactiveSince();
        $failed = [...$failed, ...$this->apply($io, $dryRun, 'Inactive accounts to warn',
            fn (array $exceptIds, ?int $limit) => $this->userRepository->findToWarnOfInactivity($inactiveSince, $exceptIds, $limit),
            $this->warnInactiveAccount,
        )];

        if ($failed) {
            $io->error(\sprintf('Failed on the accounts %s, see the logs.', implode(', ', $failed)));

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * @param callable(int[], ?int): User[] $find
     * @param callable(User): void          $action
     *
     * @return int[] the ids of the accounts the action failed on
     */
    private function apply(SymfonyStyle $io, bool $dryRun, string $title, callable $find, callable $action): array
    {
        $users = $find([], null);

        $io->section(\sprintf('%s: %d', $title, \count($users)));

        if (!$users) {
            return [];
        }

        $io->table(
            ['Id', 'Email', 'Last activity'],
            array_map(fn (User $user) => [$user->getId(), $user->getEmail(), $user->getLastActivityAt()->format('Y-m-d H:i')], $users),
        );

        if ($dryRun) {
            return [];
        }

        // One account at a time, read afresh: one that changed meanwhile (came back) is not
        // acted on, and one that fails is skipped without stopping the others.
        $failed = [];
        while ($user = $find($failed, 1)[0] ?? null) {
            $id = $user->getId();

            try {
                $action($user);
            } catch (\Throwable $exception) {
                $this->logger->error('Purging the inactive account {id} failed.', ['id' => $id, 'exception' => $exception]);
                $failed[] = $id;
                $this->doctrine->resetManager();
            }

            $this->entityManager->clear();
        }

        return $failed;
    }
}
