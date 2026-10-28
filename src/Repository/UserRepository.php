<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    use CountsCreatedPerDayTrait;

    public const MAX_PER_PAGE = 12;

    public function __construct(
        ManagerRegistry $registry,
        private PaginatorInterface $paginator,
        private Security $security,
    ) {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function search(?string $query = null, int $page = 1): PaginationInterface
    {
        /** @var User|null */
        $user = $this->security->getUser();

        $queryBuilder = $this->createFullNameSearchQueryBuilder((string) $query);

        if ($user instanceof User) {
            $queryBuilder
                ->andWhere('u.id != :currentUser')
                ->setParameter('currentUser', $user->getId())
            ;
        }

        return $this->paginator
            ->paginate(
                $queryBuilder,
                $page,
                self::MAX_PER_PAGE
            )
        ;
    }

    /**
     * Finds users whose full names match the given pattern.
     *
     * @param string $pattern The pattern to search for in user full names
     *
     * @return User[] An array of User objects matching the pattern
     */
    public function findByFullNamePattern(string $pattern): array
    {
        return $this->createFullNameSearchQueryBuilder($pattern)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * The users whose first or last name contains $query, case-insensitive: shared by the
     * front search (paginated, without the current user) and the console search.
     */
    private function createFullNameSearchQueryBuilder(string $query): QueryBuilder
    {
        return $this->createQueryBuilder('u')
            ->where('LOWER(u.firstname) LIKE :query')
            ->orWhere('LOWER(u.lastname) LIKE :query')
            ->setParameter('query', '%'.strtolower($query).'%')
        ;
    }

    /**
     * The ids of the users having the given role stored in their roles. The roles column
     * is JSON, which PostgreSQL cannot LIKE nor DQL unfold: native SQL lists its elements.
     *
     * @return list<int>
     */
    public function findIdsByRole(string $role): array
    {
        [$table, $roles] = $this->quotedTableAndColumn('roles');

        return array_map('intval', $this->getEntityManager()->getConnection()
            ->executeQuery(
                "SELECT DISTINCT u.id
                FROM $table u, json_array_elements_text(u.$roles) AS role
                WHERE role = :role",
                ['role' => $role],
            )
            ->fetchFirstColumn()
        );
    }

    /**
     * Verified accounts without activity since $inactiveSince and not scheduled for deletion
     * yet. An unverified one goes on its own rule, without an email to an address never confirmed.
     *
     * @param list<int> $exceptIds
     *
     * @return User[]
     */
    public function findToWarnOfInactivity(\DateTimeImmutable $inactiveSince, array $exceptIds = [], ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('u')
            ->where('u.isVerified = true')
            ->andWhere('u.lastActivityAt <= :inactiveSince')
            ->andWhere('u.scheduledDeletionAt IS NULL')
            ->setParameter('inactiveSince', $inactiveSince)
        ;

        return $this->resultExcept($qb, $exceptIds, $limit);
    }

    /**
     * Scheduled for deletion before $scheduledBefore, not reminded yet: an account already due
     * (scheduled before $dueBefore) gets no reminder.
     *
     * @param list<int> $exceptIds
     *
     * @return User[]
     */
    public function findToRemindOfInactivity(\DateTimeImmutable $scheduledBefore, \DateTimeImmutable $dueBefore, array $exceptIds = [], ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('u')
            ->where('u.scheduledDeletionAt <= :scheduledBefore')
            ->andWhere('u.scheduledDeletionAt > :dueBefore')
            ->andWhere('u.inactivityRemindedAt IS NULL')
            ->setParameter('scheduledBefore', $scheduledBefore)
            ->setParameter('dueBefore', $dueBefore)
        ;

        return $this->resultExcept($qb, $exceptIds, $limit);
    }

    /**
     * Accounts whose scheduled deletion date has come by $dueBefore.
     *
     * @param list<int> $exceptIds
     *
     * @return User[]
     */
    public function findToDeleteForInactivity(\DateTimeImmutable $dueBefore, array $exceptIds = [], ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('u')
            ->where('u.scheduledDeletionAt <= :dueBefore')
            ->setParameter('dueBefore', $dueBefore)
        ;

        return $this->resultExcept($qb, $exceptIds, $limit);
    }

    /**
     * Unverified accounts created and without activity since $since: the ones that never
     * confirmed their address, which UserChecker keeps from logging in. Activity is still
     * checked, for an account that was used before it became required.
     *
     * @param list<int> $exceptIds
     *
     * @return User[]
     */
    public function findUnverifiedInactiveSince(\DateTimeImmutable $since, array $exceptIds = [], ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('u')
            ->where('u.isVerified = false')
            ->andWhere('u.createdAt <= :since')
            ->andWhere('u.lastActivityAt <= :since')
            ->setParameter('since', $since)
        ;

        return $this->resultExcept($qb, $exceptIds, $limit);
    }

    /**
     * The users of $qb but $exceptIds, by id, at most $limit: the purge fetches the next
     * account still concerned one at a time, skipping those that failed.
     *
     * @param list<int> $exceptIds
     *
     * @return User[]
     */
    private function resultExcept(QueryBuilder $qb, array $exceptIds, ?int $limit): array
    {
        if ([] !== $exceptIds) {
            $qb
                ->andWhere('u.id NOT IN (:exceptIds)')
                ->setParameter('exceptIds', $exceptIds)
            ;
        }

        return $qb
            ->orderBy('u.id')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
