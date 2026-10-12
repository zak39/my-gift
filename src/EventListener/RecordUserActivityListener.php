<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * Keeps an account out of the inactive accounts' purge while its user comes back: on
 * login (remember-me included), and on any authenticated request, which covers reserving,
 * contributing or editing a list without touching each controller. A request without a
 * session cookie is skipped, so the lazy firewall does not load a user on anonymous pages:
 * a remember-me login goes through onLoginSuccess. User::recordActivity() decides whether
 * there is anything to write.
 */
class RecordUserActivityListener
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager,
        private ClockInterface $clock,
    ) {
    }

    #[AsEventListener]
    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $this->record($event->getUser());
    }

    // After the firewall (priority 8), once the user is known.
    #[AsEventListener(event: KernelEvents::REQUEST, priority: 0)]
    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || !$event->getRequest()->hasPreviousSession()) {
            return;
        }

        $this->record($this->security->getUser());
    }

    private function record(?UserInterface $user): void
    {
        if ($user instanceof User && $user->recordActivity($this->clock->now())) {
            $this->entityManager->flush();
        }
    }
}
