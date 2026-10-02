<?php

namespace App\Repository;

use App\Entity\Crew;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Crew>
 */
class CrewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Crew::class);
    }

    /**
     * The crews the user belongs to, with their members, by name.
     *
     * @return list<Crew>
     */
    public function findForMember(User $user): array
    {
        return $this->createQueryBuilder('c')
            // One join picks the user's crews, the other loads every member of them.
            ->innerJoin('c.members', 'own', 'WITH', 'own = :user')
            ->leftJoin('c.members', 'm')
            ->addSelect('m')
            ->setParameter('user', $user)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * The same, with what their cards count loaded too: sections and gifts. One query
     * instead of one per crew and section, apart from the members, whose rows the
     * sections would multiply.
     *
     * @return list<Crew>
     */
    public function findForMemberWithGifts(User $user): array
    {
        $crews = $this->findForMember($user);

        if ($crews) {
            $this->createQueryBuilder('c')
                ->leftJoin('c.sections', 's')
                ->leftJoin('s.gifts', 'g')
                ->addSelect('s', 'g')
                ->where('c IN (:crews)')
                ->setParameter('crews', $crews)
                ->getQuery()
                ->getResult()
            ;
        }

        return $crews;
    }
}
