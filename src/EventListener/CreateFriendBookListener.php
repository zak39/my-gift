<?php

namespace App\EventListener;

use App\Entity\FriendBook;
use App\Event\UserCreatedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Every user gets a friend book at registration, so that reading one (a voter, the
 * navbar) never has to create it. Users registered before the books existed got
 * theirs from the migration.
 */
final class CreateFriendBookListener
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[AsEventListener]
    public function onUserCreatedEvent(UserCreatedEvent $event): void
    {
        $this->entityManager->persist(new FriendBook($event->getUser()));
        $this->entityManager->flush();
    }
}
