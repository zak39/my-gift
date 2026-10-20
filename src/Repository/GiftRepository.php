<?php

namespace App\Repository;

use App\Entity\Gift;
use App\Entity\User;
use App\Enum\ParticipantStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Gift>
 */
class GiftRepository extends ServiceEntityRepository
{
    use CountsCreatedPerDayTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Gift::class);
    }

    /**
     * The gifts of the user's own list, filed or not. A gift filed in a crew's list
     * belongs to the crew, whoever added it: the crew's page shows it.
     *
     * @return list<Gift>
     */
    public function findPersonalOf(User $user, ?int $limit = null): array
    {
        return $this->createPersonalQueryBuilder($user)
            ->orderBy('g.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    public function countPersonalOf(User $user): int
    {
        return (int) $this->createPersonalQueryBuilder($user)
            ->select('COUNT(g.id)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * What the user's friends see of their own list: the public gifts filed in a section.
     */
    public function countPublishedPersonalOf(User $user): int
    {
        return (int) $this->createPersonalQueryBuilder($user)
            ->select('COUNT(g.id)')
            ->andWhere('s.id IS NOT NULL')
            ->andWhere('g.isPublic = true')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function createPersonalQueryBuilder(User $user): QueryBuilder
    {
        return $this->createQueryBuilder('g')
            // A gift outside any section has no crew either. Selected for getOwnership().
            ->leftJoin('g.section', 's')
            ->addSelect('s')
            ->where('g.createdBy = :user')
            ->andWhere('s.crew IS NULL')
            ->setParameter('user', $user)
        ;
    }

    /**
     * The gift, if it is filed in the crew's list: what a /crews/{id}/gifts/{gift} URL
     * reaches. Another crew's gift, or one of a user's own list, is not found there (404).
     */
    public function findOneInCrew(int $giftId, int $crewId): ?Gift
    {
        return $this->createQueryBuilder('g')
            ->innerJoin('g.section', 's')
            ->addSelect('s')
            ->where('g.id = :gift')
            ->andWhere('s.crew = :crew')
            ->setParameter('gift', $giftId)
            ->setParameter('crew', $crewId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * The gifts a user added to crews' lists, with their section and crew.
     *
     * @return list<Gift>
     */
    public function findInCrewsCreatedBy(User $user): array
    {
        return $this->createQueryBuilder('g')
            ->innerJoin('g.section', 's')
            ->innerJoin('s.crew', 'c')
            ->addSelect('s', 'c')
            ->where('g.createdBy = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Every gift of the lists the user runs: their own, and those of the crews they belong
     * to, whoever added them. With their section, crew, author and participants, for the
     * data export.
     *
     * @return list<Gift>
     */
    public function findInListsOf(User $user): array
    {
        return $this->createQueryBuilder('g')
            // Left: a crew's gift may have lost its author (an account deleted).
            ->leftJoin('g.createdBy', 'u')
            ->leftJoin('g.section', 's')
            ->leftJoin('s.crew', 'c')
            // Joined only when the user is a member: a crew's gift stays out otherwise.
            ->leftJoin('c.memberships', 'm', 'WITH', 'm.user = :user')
            ->leftJoin('g.participants', 'p')
            ->leftJoin('p.participantUser', 'pu')
            ->addSelect('u', 's', 'c', 'p', 'pu')
            ->where('c.id IS NULL AND g.createdBy = :user')
            ->orWhere('m.id IS NOT NULL')
            ->setParameter('user', $user)
            ->orderBy('s.title', 'ASC')
            ->addOrderBy('g.createdAt', 'DESC')
            // The participants in the order they joined.
            ->addOrderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * The gift, if it belongs to a user's own list, filed or not: what a /me/gifts/{id} URL
     * reaches. A crew's gift is run from its crew's URL, as the links lead there (404 here).
     */
    public function findOnePersonal(int $id): ?Gift
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.section', 's')
            ->addSelect('s')
            ->where('g.id = :id')
            ->andWhere('s.crew IS NULL')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findFavoritesGifts(User $user, ?int $limit = null)
    {
        $queryBuilder = $this->createQueryBuilder('g')
            ->innerJoin('g.favoritedBy', 'u')
            // The inner join on section drops gifts left outside one; the joins also load
            // the creator, section and crew the favourites page and the data export display.
            // Left on the creator: a crew's gift may have lost its author.
            ->leftJoin('g.createdBy', 'c')
            ->innerJoin('g.section', 's')
            ->leftJoin('s.crew', 'crew')
            ->addSelect('c', 's', 'crew')
            ->where('u.id = :userId')
            ->andWhere('g.isPublic = true')
            // The friendship rule of GiftVisibilityVoter, read from the user's own book as
            // CrewVoter does: a favourite drops out of the list once the user is no friend
            // anymore of its list's owners, the creator of a personal list or a member of a
            // crew's. The friends are read through a join, not MEMBER OF, which Doctrine
            // cannot translate on a book whose id is its owner (derived identity).
            ->andWhere(<<<'DQL'
                EXISTS (
                    SELECT viewerFriend.id
                    FROM App\Entity\FriendBook viewerBook
                    JOIN viewerBook.friends viewerFriend
                    WHERE viewerBook.owner = :currentUser
                    AND (
                        (crew.id IS NULL AND viewerFriend = c)
                        OR EXISTS (
                            SELECT membership.id
                            FROM App\Entity\CrewMember membership
                            WHERE membership.crew = crew AND membership.user = viewerFriend
                        )
                    )
                )
                DQL)
            ->setParameter('userId', $user->getId())
            ->setParameter('currentUser', $user)
            ->orderBy('g.createdAt', 'DESC')
        ;

        if ($limit) {
            $queryBuilder->setMaxResults($limit);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function findGiftByParticipant(User $user, ?ParticipantStatus $status = null): array
    {
        $queryBuilder = $this->createQueryBuilder('g')
            ->innerJoin('g.participants', 'p')
            ->where('p.participantUser = :user')
            ->setParameter('user', $user);

        if ($status) {
            $queryBuilder
                ->andWhere('p.status = :status')
                ->setParameter('status', $status)
            ;
        }

        return $queryBuilder->getQuery()->getResult();
    }

    //    /**
    //     * @return Gift[] Returns an array of Gift objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('g')
    //            ->andWhere('g.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('g.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Gift
    //    {
    //        return $this->createQueryBuilder('g')
    //            ->andWhere('g.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
