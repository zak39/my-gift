<?php

namespace App\EventListener;

use App\Event\UserCreatedEvent;
use App\Message\GenerateDefaultAvatar;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;

final class GenerateDefaultAvatarListener
{
    public function __construct(
        private MessageBusInterface $bus,
    ) {
    }

    /**
     * Only queued: the worker downloads the avatar (GenerateDefaultAvatarHandler), so that the
     * registration, and its confirmation email, never depend on DiceBear.
     */
    #[AsEventListener]
    public function onUserCreatedEvent(UserCreatedEvent $event): void
    {
        $this->bus->dispatch(new GenerateDefaultAvatar($event->getUser()->getId()));
    }
}
