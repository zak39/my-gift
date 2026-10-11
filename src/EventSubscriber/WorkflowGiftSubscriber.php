<?php

namespace App\EventSubscriber;

use App\Entity\Gift;
use App\Entity\Participant;
use App\Enum\GiftStatus;
use App\Event\GiftNoLongerReadyEvent;
use App\Event\GiftReadyEvent;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Event\EnteredEvent;
use Symfony\Component\Workflow\Event\TransitionEvent;

class WorkflowGiftSubscriber implements EventSubscriberInterface
{
    public const FROM_EMAIL = 'contact@zakode.dev';

    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function giftReserved(EnteredEvent $event): void
    {
        /** @var Gift $gift */
        $gift = $event->getSubject();

        $participant = $gift->getParticipants()->first();

        $email = (new TemplatedEmail())
            ->from(self::FROM_EMAIL)
            ->to($gift->getCreatedBy()->getEmail())
            ->subject('Votre demande de cadeau a été réservée ! 🎁')
            ->context([
                'gift' => $gift,
                'url' => $this->urlGenerator->generate('app_gift_show', ['id' => $gift->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                'participant' => $participant,
            ])
            ->htmlTemplate('emails/gift_reserved.html.twig')
        ;

        $this->mailer->send($email);
    }

    /**
     * Whoever reserved the gift no longer offers it: a reservation released (`disengage`),
     * or a giver gone with their account from a reservation they had declared ready
     * (`release`).
     */
    public function giftDisengaged(TransitionEvent $event): void
    {
        /** @var Gift $gift */
        $gift = $event->getSubject();
        /** @var Participant $participant */
        $participant = $event->getContext()['participant'];

        $participantName = ucwords($participant->getDisplayName());

        $subject = "Mince ! $participantName a retiré sa réservation ! 😢";

        $email = (new TemplatedEmail())
            ->from(self::FROM_EMAIL)
            ->to($gift->getCreatedBy()->getEmail())
            ->subject($subject)
            ->context([
                'gift' => $gift,
                'url' => $this->urlGenerator->generate('app_gift_show', ['id' => $gift->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                'participant' => $participant,
            ])
            ->htmlTemplate('emails/gift_disengaged.html.twig')
        ;

        $this->mailer->send($email);
    }

    public function giftContributing(CompletedEvent $event): void
    {
        /** @var Gift $gift */
        $gift = $event->getSubject();

        $participant = $event->getContext()['participant'];

        $ownerName = ucwords($gift->getCreatedBy()->getFirstname());

        $email = (new TemplatedEmail())
            ->from(self::FROM_EMAIL)
            ->to($participant->getParticipantUser()->getEmail())
            ->subject("Vous participez au cadeau de $ownerName ! 🎁")
            ->context([
                'participant' => $participant,
                'gift' => $gift,
                'url' => $this->urlGenerator->generate('app_gift_show', ['id' => $gift->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            ])
            ->htmlTemplate('emails/gift_contributing.html.twig')
        ;

        $this->mailer->send($email);
    }

    public function giftCancelContribution(TransitionEvent $event): void
    {
        // Their account is being deleted (PoolContributionRelease, ReadyPoolRelease): there
        // is nobody left to tell.
        if ($event->getContext()['accountDeleted'] ?? false) {
            return;
        }

        /** @var Gift $gift */
        $gift = $event->getSubject();

        $participant = $event->getContext()['participant'];

        $ownerName = ucwords($gift->getCreatedBy()->getFirstname());

        $email = (new TemplatedEmail())
            ->from(self::FROM_EMAIL)
            ->to($participant->getParticipantUser()->getEmail())
            ->subject("Vous êtiez le dernier participant au cadeau de $ownerName ! 😢")
            ->context([
                'participant' => $participant,
                'gift' => $gift,
                'url' => $this->urlGenerator->generate('app_gift_show', ['id' => $gift->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            ])
            ->htmlTemplate('emails/gift_cancelled_contribution.html.twig')
        ;

        $this->mailer->send($email);
    }

    /**
     * The owner was told the pool was complete: it no longer is.
     */
    public function giftNoLongerReady(GiftNoLongerReadyEvent $event): void
    {
        $gift = $event->getGift();

        $email = (new TemplatedEmail())
            ->from(self::FROM_EMAIL)
            ->to($gift->getCreatedBy()->getEmail())
            ->subject("Votre cadeau {$gift->getTitle()} n'est plus prêt")
            ->context([
                'gift' => $gift,
                'backOnOffer' => GiftStatus::CREATED === $gift->getStatus(),
                'url' => $this->urlGenerator->generate('app_gift_show', ['id' => $gift->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            ])
            ->htmlTemplate('emails/gift_no_longer_ready.html.twig')
        ;

        $this->mailer->send($email);
    }

    public function giftReady(GiftReadyEvent $event): void
    {
        $gift = $event->getGift();

        $email = (new TemplatedEmail())
            ->from(self::FROM_EMAIL)
            ->to($gift->getCreatedBy()->getEmail())
            ->subject("Votre cadeau {$gift->getTitle()} est prêt ! 🎁")
            ->context([
                'gift' => $gift,
                'url' => $this->urlGenerator->generate('app_gift_show', ['id' => $gift->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            ])
            ->htmlTemplate('emails/gift_ready_for_gift_owner.html.twig')
        ;

        $this->mailer->send($email);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.gift_request.entered.reserved' => 'giftReserved',
            'workflow.gift_request.transition.disengage' => 'giftDisengaged',
            // Not `entered.contributing`: `reopen` enters that place too, without a participant.
            'workflow.gift_request.completed.contribute' => 'giftContributing',
            'workflow.gift_request.transition.cancelled_contribution' => 'giftCancelContribution',
            'workflow.gift_request.transition.release' => 'giftDisengaged',
            // Only a pool declared ready, which `leave.contributing` cannot tell from a cancelled one.
            GiftReadyEvent::class => 'giftReady',
            GiftNoLongerReadyEvent::class => 'giftNoLongerReady',
        ];
    }
}
