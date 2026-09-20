<?php

namespace App\EventSubscriber;

use App\Enum\GiftStatus;
use App\Event\GiftReadyEvent;
use App\Event\ParticipantJoinedEvent;
use App\Event\ParticipantLeftEvent;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ParticipantJoinedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function newParticipant(ParticipantJoinedEvent $event): void
    {
        $gift = $event->getGift();
        $participant = $event->getParticipant();

        if (GiftStatus::CONTRIBUTING !== $gift->getStatus()) {
            // The gift is not in the CONTRIBUTING state, so we don't need to do anything.
            return;
        }

        $participants = $gift->getParticipants();
        $participantsMail = array_map(fn ($participant) => $participant->getParticipantUser()->getEmail(), $participants->toArray());

        $giftOwner = $gift->getCreatedBy();
        $firstnameOwner = ucfirst($giftOwner->getFirstname());

        $email = (new TemplatedEmail())
            ->from('contact@zakode.dev')
            ->to(...$participantsMail)
            ->subject("Un nouveau participant a rejoint pour le cadeau de $firstnameOwner")
            ->htmlTemplate('emails/new_participant.html.twig')
            ->context([
                'gift' => $gift,
                'participant' => $participant,
                'url' => $this->urlGenerator->generate('app_gift_show', ['id' => $gift->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            ])
        ;

        $this->mailer->send($email);
    }

    public function participantLeft(ParticipantLeftEvent $event): void
    {
        $gift = $event->getGift();
        $participant = $event->getParticipant();

        if (GiftStatus::CONTRIBUTING !== $gift->getStatus()) {
            // The gift is not in the CONTRIBUTING state, so we don't need to do anything.
            return;
        }

        $participants = $gift->getParticipants();
        $participantsMail = array_map(fn ($participant) => $participant->getParticipantUser()->getEmail(), $participants->toArray());

        $giftOwner = $gift->getCreatedBy();
        $firstnameOwner = ucfirst($giftOwner->getFirstname());

        $email = (new TemplatedEmail())
            ->from('contact@zakode.dev')
            ->to(...$participantsMail)
            ->subject("Un participant a quitté le cadeau de $firstnameOwner")
            ->htmlTemplate('emails/participant_left.html.twig')
            ->context([
                'gift' => $gift,
                'participant' => $participant,
                'url' => $this->urlGenerator->generate('app_gift_show', ['id' => $gift->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            ])
        ;

        $this->mailer->send($email);
    }

    // regarder pourquoi ça ne marche pas
    public function giftReady(GiftReadyEvent $event): void
    {
        $gift = $event->getGift();

        $participants = $gift->getParticipants();
        $participantsMail = array_map(fn ($participant) => $participant->getParticipantUser()->getEmail(), $participants->toArray());

        $giftOwner = $gift->getCreatedBy();
        $firstnameOwner = ucfirst($giftOwner->getFirstname());

        $email = (new TemplatedEmail())
            ->from('contact@zakode.dev')
            ->to(...$participantsMail)
            ->subject("Le cadeau de $firstnameOwner est prêt ! 🎉")
            ->htmlTemplate('emails/gift_ready.html.twig')
            ->context([
                'gift' => $gift,
                'url' => $this->urlGenerator->generate('app_gift_show', ['id' => $gift->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            ])
        ;

        $this->mailer->send($email);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ParticipantJoinedEvent::class => 'newParticipant',
            ParticipantLeftEvent::class => 'participantLeft',
            GiftReadyEvent::class => 'giftReady',
        ];
    }
}
