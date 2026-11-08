<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface, UserLoaderInterface
{
    use CountsCreatedPerDayTrait;

    public const MAX_PER_PAGE = 12;

    /**
     * The full name searched whole, so that "Jean Dupont" finds him: COALESCE because the
     * last name is optional and, on PostgreSQL, CONCAT with a null gives null.
     */
    private const FULL_NAME_MATCHES = "LOWER(CONCAT(u.firstname, ' ', COALESCE(u.lastname, ''))) LIKE :query";

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

    /**
     * The login form's identifier: an email, or a pseudo in any case. A pseudo holds no "@",
     * so an email and a pseudo never match two different users.
     *
     * One user at most, the pseudo typed exactly first: the database's unique index tells
     * cases apart, so "Jean" and "jean" could both exist (a write past SaveUserPseudo's lock,
     * from EasyAdmin). Each then still logs in with their own, rather than both getting an
     * error for a non-unique result.
     */
    public function loadUserByIdentifier(string $identifier): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.email = :identifier')
            ->orWhere('LOWER(u.pseudo) = :pseudo')
            ->orderBy('CASE WHEN u.pseudo = :identifier THEN 0 ELSE 1 END')
            ->addOrderBy('u.id')
            ->setParameter('identifier', $identifier)
            ->setParameter('pseudo', strtolower($identifier))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * UniqueEntity's lookup for the pseudo: "Jean" and "jean" are the same one.
     *
     * @param array{pseudo: string} $criteria
     *
     * @return User[]
     */
    public function findByPseudoIgnoringCase(array $criteria): array
    {
        return $this->createQueryBuilder('u')
            ->where('LOWER(u.pseudo) = :pseudo')
            ->setParameter('pseudo', strtolower($criteria['pseudo']))
            // Two are enough for UniqueEntity to tell, as its own findBy() does.
            ->setMaxResults(2)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Holds the pseudo, whatever its case, until the current transaction ends: a second save
     * of the same pseudo waits here, then sees the first one. Only inside a transaction,
     * which releases it. Two pseudos may share a lock (hashtext() gives an int): one then
     * waits for the other, nothing worse.
     */
    public function lockPseudo(string $pseudo): void
    {
        $this->getEntityManager()->getConnection()->executeStatement(
            'SELECT pg_advisory_xact_lock(hashtext(:key))',
            ['key' => 'pseudo:'.strtolower($pseudo)],
        );
    }

    /**
     * The pseudos taken among those starting with $prefix, in lowercase: what a suggestion
     * built on $prefix must avoid, in one query.
     *
     * @return list<string>
     */
    public function findPseudosStartingWith(string $prefix): array
    {
        return $this->createQueryBuilder('u')
            ->select('LOWER(u.pseudo)')
            ->where('LOWER(u.pseudo) LIKE :prefix')
            // "_" is a LIKE wildcard: a few more pseudos than needed, never one missed.
            ->setParameter('prefix', strtolower($prefix).'%')
            ->getQuery()
            ->getSingleColumnResult()
        ;
    }

    public function search(?string $query = null, int $page = 1): PaginationInterface
    {
        /** @var User|null */
        $user = $this->security->getUser();

        $queryBuilder = $this->createSearchQueryBuilder((string) $query);

        if ($user instanceof User) {
            // The full name among the viewer's friends only, the ones who see it
            // (user.can_see_fullname): a match on it would tell a stranger whose pseudo it
            // is. The friends are read through a join, not MEMBER OF, which Doctrine cannot
            // translate on a book whose id is its owner (derived identity).
            $queryBuilder
                ->orWhere('('.self::FULL_NAME_MATCHES.<<<'DQL'
                     AND EXISTS (
                        SELECT viewerFriend.id
                        FROM App\Entity\FriendBook viewerBook
                        JOIN viewerBook.friends viewerFriend
                        WHERE viewerBook.owner = :currentUser AND viewerFriend = u
                    ))
                    DQL)
                ->andWhere('u.id != :currentUser')
                ->setParameter('currentUser', $user)
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
     * Finds users whose pseudo or full name match the given pattern, any full name: for the
     * console's admin, not for a viewer (see search()).
     *
     * @param string $pattern The pattern to search for in user pseudos and full names
     *
     * @return User[] An array of User objects matching the pattern
     */
    public function findBySearchPattern(string $pattern): array
    {
        return $this->createSearchQueryBuilder($pattern)
            ->orWhere(self::FULL_NAME_MATCHES)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * The users whose pseudo contains $query, case-insensitive: shared by the front search
     * (search()) and the console's (findBySearchPattern()), each adding the full names it
     * may search with FULL_NAME_MATCHES on the same :query.
     */
    private function createSearchQueryBuilder(string $query): QueryBuilder
    {
        return $this->createQueryBuilder('u')
            ->where('LOWER(u.pseudo) LIKE :query')
            // "_" may be in a pseudo, but is a LIKE wildcard, as "%": both escaped, "jean_1"
            // finds jean_1, not jeanx1 (PostgreSQL's LIKE escapes with a backslash).
            ->setParameter('query', '%'.addcslashes(mb_strtolower($query), '%_\\').'%')
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
