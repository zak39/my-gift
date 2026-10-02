<?php

namespace App\Repository;

use App\Entity\Section;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Section>
 */
class SectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Section::class);
    }

    /**
     * The sections of the user's own list. Not User::getSections(), which also holds the
     * sections they created in a crew's list.
     *
     * @return list<Section>
     */
    public function findPersonalOf(User $user): array
    {
        return $this->findBy(['createdBy' => $user, 'crew' => null], ['id' => 'ASC']);
    }

    /**
     * Every section the user may file a gift in: those of their own list, and those of
     * their crews' lists, whoever created them.
     *
     * @return list<Section>
     */
    public function findManagedBy(User $user): array
    {
        return $this->createQueryBuilder('s')
            // Selected for the crew's name GiftEditType groups the sections by.
            ->leftJoin('s.crew', 'c')
            ->addSelect('c')
            ->leftJoin('c.members', 'm', 'WITH', 'm = :user')
            ->where('c.id IS NULL AND s.createdBy = :user')
            ->orWhere('m.id IS NOT NULL')
            ->setParameter('user', $user)
            ->orderBy('c.name', 'ASC')
            ->addOrderBy('s.title', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    //    /**
    //     * @return Section[] Returns an array of Section objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Section
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
