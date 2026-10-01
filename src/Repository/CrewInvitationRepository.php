<?php

namespace App\Repository;

use App\Entity\CrewInvitation;
use App\Entity\User;
use App\Enum\CrewInvitationStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CrewInvitation>
 */
class CrewInvitationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CrewInvitation::class);
    }

    /**
     * The invitations waiting for the user's answer, with the crew and the inviter
     * their cards show.
     *
     * @return list<CrewInvitation>
     */
    public function findPendingReceivedBy(User $user): array
    {
        return $this->createQueryBuilder('i')
            ->innerJoin('i.crew', 'c')
            ->innerJoin('i.invitedBy', 'inviter')
            ->addSelect('c', 'inviter')
            ->where('i.invitee = :user')
            ->andWhere('i.status = :pending')
            ->setParameter('user', $user)
            ->setParameter('pending', CrewInvitationStatus::PENDING)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * A single COUNT for the navbar badge, run on every page.
     */
    public function countPendingReceivedBy(User $user): int
    {
        return $this->count([
            'invitee' => $user,
            'status' => CrewInvitationStatus::PENDING,
        ]);
    }
}
