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
            ->innerJoin('c.memberships', 'own', 'WITH', 'own.user = :user')
            ->leftJoin('c.memberships', 'ms')
            ->leftJoin('ms.user', 'm')
            ->addSelect('ms', 'm')
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
        return $this->loadGifts($this->findForMember($user), false);
    }

    /**
     * The same, ready for their sections' gift cards (Organism:Section:Listing): each gift's
     * givers too. Still one query: each join follows the one before (a crew's sections,
     * their gifts, their givers), so no row is multiplied by a sibling collection.
     *
     * @return list<Crew>
     */
    public function findForMemberWithGiftCards(User $user): array
    {
        return $this->loadGifts($this->findForMember($user), true);
    }

    /**
     * @param list<Crew> $crews
     *
     * @return list<Crew>
     */
    private function loadGifts(array $crews, bool $withGivers): array
    {
        if (!$crews) {
            return $crews;
        }

        $query = $this->createQueryBuilder('c')
            ->leftJoin('c.sections', 's')
            ->leftJoin('s.gifts', 'g')
            ->addSelect('s', 'g')
            ->where('c IN (:crews)')
            ->setParameter('crews', $crews)
        ;

        if ($withGivers) {
            $query
                ->leftJoin('g.participants', 'p')
                ->leftJoin('p.participantUser', 'pu')
                ->addSelect('p', 'pu')
            ;
        }

        $query->getQuery()->getResult();

        return $crews;
    }
}
