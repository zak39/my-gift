<?php

namespace App\EventSubscriber;

use App\Event\FriendRequestSentEvent;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Tells a user that someone sent them a friend request. Nothing on a refusal, so as not to
 * hurt its sender, nor on a block, which the blocked user must never learn about. At most one
 * a day per sender and recipient (friend_request_email, config/packages/rate_limiter.yaml):
 * sending, cancelling and sending again must not flood their inbox. The request is there all
 * the same. The sender is the mailer's (config/packages/mailer.yaml).
 *
 * The context holds the users the email shows, not the request: it is deleted once
 * answered, and the email is rendered by the worker, sometimes after that.
 */
class FriendNotificationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger,
        private RateLimiterFactoryInterface $friendRequestEmailLimiter,
    ) {
    }

    public function requestSent(FriendRequestSentEvent $event): void
    {
        $friendRequest = $event->getFriendRequest();
        $sender = $friendRequest->getSender()->getOwner();
        $recipient = $friendRequest->getRecipient()->getOwner();

        $limiter = $this->friendRequestEmailLimiter->create($sender->getId().'-'.$recipient->getId());

        if (!$limiter->consume()->isAccepted()) {
            $this->logger->info('Friend request email skipped: one went out less than a day ago.', [
                'sender' => $sender->getId(),
                'recipient' => $recipient->getId(),
            ]);

            return;
        }

        $email = (new TemplatedEmail())
            ->to($recipient->getEmail())
            ->subject(\sprintf('%s souhaite vous ajouter à ses ami·es 🎁', ucfirst($sender->getFirstname())))
            ->htmlTemplate('emails/friend_request.html.twig')
            ->context([
                'sender' => $sender,
                'recipient' => $recipient,
                // The requests received wait on « Mes ami·es », with their answer buttons.
                'url' => $this->urlGenerator->generate('app_friend', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ])
        ;

        try {
            $this->mailer->send($email);
        } catch (TransportException|TransportExceptionInterface $exception) {
            // The request is saved and waits on the recipient's page: an email that cannot be
            // queued (Messenger) or sent (a synchronous mailer) must not turn it into an error.
            // Nor use up the day's email: the next request of this pair may still send one.
            $limiter->reset();
            $this->logger->warning('Friend request saved, but its email could not be sent.', [
                'exception' => $exception,
            ]);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FriendRequestSentEvent::class => 'requestSent',
        ];
    }
}
