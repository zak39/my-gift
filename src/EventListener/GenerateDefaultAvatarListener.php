<?php

namespace App\EventListener;

use App\Event\UserCreatedEvent;
use App\Services\DicebearAvatarGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class GenerateDefaultAvatarListener
{
    public function __construct(
        private DicebearAvatarGenerator $avatarGenerator,
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[AsEventListener]
    public function onUserCreatedEvent(UserCreatedEvent $event): void
    {
        $user = $event->getUser();
        $uploadedFile = $this->avatarGenerator->generate($user->getFirstname());
        $user->setAvatarDefaultFile($uploadedFile);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
