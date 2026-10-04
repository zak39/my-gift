<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Event\UserCreatedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class EasyAdminUserSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityPersistedEvent::class => [
                ['hashPassword'],
            ],
            BeforeEntityUpdatedEvent::class => [
                ['hashPassword'],
            ],
            AfterEntityPersistedEvent::class => [
                ['dispatchUserCreated'],
            ],
        ];
    }

    public function hashPassword(BeforeEntityPersistedEvent|BeforeEntityUpdatedEvent $event): void
    {
        $user = $event->getEntityInstance();
        if (!$user instanceof User) {
            return;
        }

        $plainPassword = $user->getPlainPassword();
        if ($plainPassword) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);
            // Never keep the plain password once hashed: the user is serialized into the
            // session, and an admin editing their own account would store it there in clear.
            $user->setPlainPassword(null);
        }
    }

    public function dispatchUserCreated(AfterEntityPersistedEvent $event): void
    {
        $user = $event->getEntityInstance();
        if (!$user instanceof User) {
            return;
        }

        $this->dispatcher->dispatch(new UserCreatedEvent($user));
    }
}
