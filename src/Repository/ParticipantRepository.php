<?php

namespace App\Repository;

use App\Entity\Gift;
use App\Entity\Participant;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Participant>
 */
class ParticipantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participant::class);
    }

    /**
     * The user's reservations and contributions, with their gift, its author, section and
     * crew, for the data export.
     *
     * @return list<Participant>
     */
    public function findOfUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.gift', 'g')
            // Left: a crew's gift may have lost its author (an account deleted).
            ->leftJoin('g.createdBy', 'u')
            ->leftJoin('g.section', 's')
            ->leftJoin('s.crew', 'c')
            ->addSelect('g', 'u', 's', 'c')
            ->where('p.participantUser = :user')
            ->setParameter('user', $user)
            ->orderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * The gift's participations read again, in place, as committed: the gift read again
     * (GiftRepository::findOneLocked()) reloads its collection, but the identity map hands
     * back the participations already loaded, with their amounts of then. $except is left
     * as it is (GiftLifecycle::whileLocked()).
     */
    public function refreshIn(Gift $gift, ?Participant $except): void
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->where('p.gift = :gift')
            ->setParameter('gift', $gift)
        ;

        // A newcomer is in no row yet.
        if (null !== $except?->getId()) {
            $queryBuilder
                ->andWhere('p.id != :except')
                ->setParameter('except', $except->getId())
            ;
        }

        $queryBuilder
            ->getQuery()
            ->setHint(Query::HINT_REFRESH, true)
            ->getResult()
        ;
    }

    //    /**
    //     * @return Participant[] Returns an array of Participant objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Participant
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
