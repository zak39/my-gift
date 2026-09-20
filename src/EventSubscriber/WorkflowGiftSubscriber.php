<?php

namespace App\EventSubscriber;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Workflow\Event\EnteredEvent;
use Symfony\Component\Workflow\Event\LeaveEvent;
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
        /** @var \App\Entity\Gift $gift */
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

    public function giftDisengaged(TransitionEvent $event): void
    {
        /** @var \App\Entity\Gift $gift */
        $gift = $event->getSubject();

        $participant = $event->getContext()['participant'];
        $participantName = $participant->isAnonymous() ? $participant->getPseudoAnonyme() : $participant->getParticipantUser()->getFullName();
        $participantName = ucwords($participantName);

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

    public function giftContributing(EnteredEvent $event): void
    {
        /** @var \App\Entity\Gift $gift */
        $gift = $event->getSubject();

        $participant = $event->getContext()['participant'];
        $participantName = $participant->isAnonymous() ? $participant->getPseudoAnonyme() : $participant->getParticipantUser()->getFullName();
        $participantName = ucwords($participantName);

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
        /** @var \App\Entity\Gift $gift */
        $gift = $event->getSubject();

        $participant = $event->getContext()['participant'];
        $participantName = $participant->isAnonymous() ? $participant->getPseudoAnonyme() : $participant->getParticipantUser()->getFullName();
        $participantName = ucwords($participantName);

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

    public function giftReady(LeaveEvent $event): void
    {
        /** @var \App\Entity\Gift $gift */
        $gift = $event->getSubject();

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
            'workflow.gift_request.entered.contributing' => 'giftContributing',
            'workflow.gift_request.transition.cancelled_contribution' => 'giftCancelContribution',
            'workflow.gift_request.leave.contributing' => 'giftReady',
        ];
    }
}
