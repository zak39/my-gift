<?php

namespace App\Repository;

use App\Entity\FriendBook;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FriendBook>
 */
class FriendBookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FriendBook::class);
    }

    /**
     * A find() by primary key, since the book shares its owner's id: after the first
     * call in a request, the identity map answers without a query.
     */
    public function of(User $user): FriendBook
    {
        return $this->find($user->getId())
            ?? throw new \LogicException(\sprintf('User %d has no friend book: CreateFriendBookListener creates one at registration.', $user->getId()))
        ;
    }
}
