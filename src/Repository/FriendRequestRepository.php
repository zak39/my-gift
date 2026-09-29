<?php

namespace App\Repository;

use App\Entity\FriendRequest;
use App\Entity\User;
use App\Enum\FriendRequestStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FriendRequest>
 */
class FriendRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FriendRequest::class);
    }

    /**
     * A single COUNT for the navbar badge, run on every page. The book shares its owner's
     * id, so the user id is the recipient_id to look for: no book to load. Not
     * getReference(): its proxy cannot take a bare id when the id is an association.
     */
    public function countPendingReceivedBy(User $user): int
    {
        return $this->count([
            'recipient' => $user->getId(),
            'status' => FriendRequestStatus::PENDING,
        ]);
    }
}
