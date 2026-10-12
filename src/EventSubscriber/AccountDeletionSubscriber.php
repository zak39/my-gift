<?php

namespace App\EventSubscriber;

use App\Entity\Participant;
use App\Enum\AccountDeletionReason;
use App\Event\AccountDeletedEvent;
use App\Event\AccountInactivityNoticeEvent;
use App\Event\GiftDeletedWithAccountEvent;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * The emails of an account's deletion: the warnings of an inactive one, its confirmation,
 * and a word to whoever was giving one of its gifts. The sender is the mailer's (config/packages/mailer.yaml).
 *
 * The contexts hold plain values only: the emails are rendered by the worker once the
 * account, its gifts and their images are gone.
 */
class AccountDeletionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function accountDeleted(AccountDeletedEvent $event): void
    {
        // An address never confirmed may not be theirs.
        if (AccountDeletionReason::Unverified === $event->getReason()) {
            return;
        }

        [$subject, $template] = AccountDeletionReason::Inactivity === $event->getReason()
            ? ['Votre compte My Gift a été supprimé', 'emails/account_deleted_inactive.html.twig']
            : ['Votre compte My Gift a bien été supprimé', 'emails/account_deleted.html.twig']
        ;

        $email = (new TemplatedEmail())
            ->to($event->getEmail())
            ->subject($subject)
            ->htmlTemplate($template)
            ->context([
                'firstname' => $event->getFirstname(),
                'url' => $this->urlGenerator->generate('app_landing_page', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ])
        ;

        $this->mailer->send($email);
    }

    /**
     * The warning and the reminder before an inactive account goes: logging in is all it
     * takes to keep it, so the button leads there.
     */
    public function accountInactivityNotice(AccountInactivityNoticeEvent $event): void
    {
        $user = $event->getUser();
        $deletionDate = $event->getDeletionDate()->format('d/m/Y');

        $email = (new TemplatedEmail())
            ->to($user->getEmail())
            ->subject($event->isReminder()
                ? \sprintf('Rappel : votre compte My Gift sera supprimé le %s', $deletionDate)
                : \sprintf('Votre compte My Gift sera supprimé le %s', $deletionDate)
            )
            ->htmlTemplate('emails/account_inactivity_warning.html.twig')
            ->context([
                'firstname' => $user->getFirstname(),
                'deletionDate' => $deletionDate,
                'reminder' => $event->isReminder(),
                'url' => $this->urlGenerator->generate('app_login', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ])
        ;

        $this->mailer->send($email);
    }

    /**
     * One email to all its givers, as the other gift emails: a reservation or a pool may
     * have meant a purchase or money changing hands outside the app.
     */
    public function giftDeletedWithAccount(GiftDeletedWithAccountEvent $event): void
    {
        $gift = $event->getGift();

        // Once given, there is nothing left to tell its givers.
        if (!$gift->getStatus()->isInProgress()) {
            return;
        }

        $recipients = $gift->getParticipantsToNotify();

        if (!$recipients) {
            return;
        }

        $ownerName = $gift->getCreatedBy()->getFirstname();

        $email = (new TemplatedEmail())
            ->to(...array_map(fn (Participant $participant) => $participant->getParticipantUser()->getEmail(), $recipients))
            ->subject(\sprintf('Le cadeau « %s » de %s n\'existe plus', $gift->getTitle(), ucfirst($ownerName)))
            ->htmlTemplate('emails/gift_deleted_with_account.html.twig')
            ->context([
                'giftTitle' => $gift->getTitle(),
                'ownerName' => $ownerName,
                // A ready gift may come from a reservation or from a pool.
                'pooled' => $gift->getPool()->isPooled(),
            ])
        ;

        $this->mailer->send($email);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AccountDeletedEvent::class => 'accountDeleted',
            AccountInactivityNoticeEvent::class => 'accountInactivityNotice',
            GiftDeletedWithAccountEvent::class => 'giftDeletedWithAccount',
        ];
    }
}
