<?php

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Http\EventListener\IsCsrfTokenValidAttributeListener;

/**
 * #[IsCsrfTokenValid] as the manual isCsrfTokenValid() checks did, on two points:
 * - checked after #[IsGranted] (priority 20): Symfony runs it before (priority 25), so under
 *   /me an outsider would get the token refused instead of the owner voter's 404, and could
 *   tell someone else's gift exists;
 * - a refused token is an AccessDeniedException, as createAccessDeniedException() threw:
 *   Symfony's InvalidCsrfTokenException is an AuthenticationException, which the firewall
 *   answers by redirecting to the login page, even a logged-in user or the favorites' fetch.
 *
 * A subscriber, not an #[AsEventListener]: a decorator inherits the kernel.event_subscriber tag
 * of the service it replaces, and the container refuses a tagged service that is not one.
 */
#[AsDecorator('controller.is_csrf_token_valid_attribute_listener')]
final class RefuseInvalidCsrfTokenListener implements EventSubscriberInterface
{
    public function __construct(
        #[AutowireDecorated] private IsCsrfTokenValidAttributeListener $inner,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::CONTROLLER_ARGUMENTS => ['onKernelControllerArguments', 15]];
    }

    public function onKernelControllerArguments(ControllerArgumentsEvent $event): void
    {
        try {
            $this->inner->onKernelControllerArguments($event);
        } catch (InvalidCsrfTokenException $exception) {
            throw new AccessDeniedException($exception->getMessage(), $exception);
        }
    }
}
