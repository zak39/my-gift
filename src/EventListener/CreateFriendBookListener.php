<?php

namespace App\EventListener;

use App\Entity\FriendBook;
use App\Event\UserCreatedEvent;
use App\Services\Friend\InvitationSlugGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Every user gets a friend book at registration, so that reading one (a voter, the
 * navbar) never has to create it, with its invitation link ready to send. Users
 * registered before the books existed got theirs from the migration.
 */
final class CreateFriendBookListener
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private InvitationSlugGenerator $invitationSlugGenerator,
    ) {
    }

    #[AsEventListener]
    public function onUserCreatedEvent(UserCreatedEvent $event): void
    {
        $this->entityManager->persist(new FriendBook($event->getUser(), ($this->invitationSlugGenerator)()));
        $this->entityManager->flush();
    }
}
