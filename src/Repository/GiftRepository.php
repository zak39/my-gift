<?php

namespace App\Repository;

use App\Entity\FriendBook;
use App\Entity\Gift;
use App\Entity\User;
use App\Enum\ParticipantStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Gift>
 */
class GiftRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Gift::class);
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
            ->where('u.id = :userId')
            ->andWhere('g.isPublic = true')
            // The friendship rule of GiftVisibilityVoter: a favourite whose creator is no
            // longer a friend drops out of the list. A join rather than MEMBER OF, which
            // Doctrine cannot translate on a book whose id is its owner (derived identity).
            ->innerJoin(FriendBook::class, 'creatorBook', 'WITH', 'creatorBook.owner = c')
            ->innerJoin('creatorBook.friends', 'creatorFriend', 'WITH', 'creatorFriend = :currentUser')
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
