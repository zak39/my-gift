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

    private function createPersonalQueryBuilder(User $user): QueryBuilder
    {
        return $this->createQueryBuilder('g')
            // A gift outside any section has no crew either. Selected for isOwnedBy().
            ->leftJoin('g.section', 's')
            ->addSelect('s')
            ->where('g.createdBy = :user')
            ->andWhere('s.crew IS NULL')
            ->setParameter('user', $user)
        ;
    }

    public function findFavoritesGifts(User $user, ?int $limit = null)
    {
        $queryBuilder = $this->createQueryBuilder('g')
            ->innerJoin('g.favoritedBy', 'u')
            // The inner join on section drops gifts left outside one; both joins also
            // load the creator and section the favourites page displays.
            ->innerJoin('g.createdBy', 'c')
            ->innerJoin('g.section', 's')
            ->addSelect('c', 's')
            ->leftJoin('s.crew', 'crew')
            ->where('u.id = :userId')
            ->andWhere('g.isPublic = true')
            // The friendship rule of GiftVisibilityVoter, read from the user's own book as
            // CrewVoter does: a favourite drops out of the list once the user is no friend
            // anymore of its list's owners, the creator of a personal list or a member of a
            // crew's. MEMBER OF works on a crew's members, not on the book's friends, which
            // Doctrine cannot translate on a book whose id is its owner (derived identity).
            ->andWhere(<<<'DQL'
                EXISTS (
                    SELECT viewerFriend.id
                    FROM App\Entity\FriendBook viewerBook
                    JOIN viewerBook.friends viewerFriend
                    WHERE viewerBook.owner = :currentUser
                    AND ((crew.id IS NULL AND viewerFriend = c) OR viewerFriend MEMBER OF crew.members)
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
