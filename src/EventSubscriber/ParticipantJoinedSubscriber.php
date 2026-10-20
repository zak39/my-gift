<?php

namespace App\EventSubscriber;

use App\Entity\Gift;
use App\Entity\Participant;
use App\Enum\GiftStatus;
use App\Event\GiftPriceChangedEvent;
use App\Event\GiftReadyEvent;
use App\Event\ParticipantJoinedEvent;
use App\Event\ParticipantLeftEvent;
use App\Services\Gift\GiftOwnerLabel;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ParticipantJoinedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        private GiftOwnerLabel $giftOwnerLabel,
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

        $this->sendEmailToParticipants(
            $gift,
            'Un nouveau participant a rejoint pour le cadeau %s',
            'emails/new_participant.html.twig',
            ['participant' => $participant],
            $participant,
        );
    }

    public function participantLeft(ParticipantLeftEvent $event): void
    {
        $gift = $event->getGift();

        if (GiftStatus::CONTRIBUTING !== $gift->getStatus()) {
            // The gift is not in the CONTRIBUTING state, so we don't need to do anything.
            return;
        }

        $this->sendEmailToParticipants(
            $gift,
            'Un participant a quitté le cadeau %s',
            'emails/participant_left.html.twig',
            ['participant' => $event->getParticipant()],
        );
    }

    // regarder pourquoi ça ne marche pas
    public function giftReady(GiftReadyEvent $event): void
    {
        $this->sendEmailToParticipants(
            $event->getGift(),
            'Le cadeau %s est prêt ! 🎉',
            'emails/gift_ready.html.twig',
        );
    }

    public function giftPriceChanged(GiftPriceChangedEvent $event): void
    {
        $this->sendEmailToParticipants(
            $event->getGift(),
            'Le prix du cadeau %s a changé',
            'emails/gift_price_changed.html.twig',
            ['previousPrice' => $event->getPreviousPrice()],
        );
    }

    /**
     * @param string       $subject  a sprintf() pattern receiving whose gift it is: « de Jon », « du groupe … »
     * @param ?Participant $excluded left out of the recipients, such as the newcomer being announced
     */
    private function sendEmailToParticipants(Gift $gift, string $subject, string $template, array $context = [], ?Participant $excluded = null): void
    {
        $recipients = array_filter(
            $gift->getParticipantsToNotify(),
            fn (Participant $participant) => $participant !== $excluded,
        );

        // Nobody else in the pool: its first contributor joining, or its last one leaving.
        if (!$recipients) {
            return;
        }

        $participantsMail = array_map(fn ($participant) => $participant->getParticipantUser()->getEmail(), $recipients);
        $ownerOf = $this->giftOwnerLabel->of($gift);

        $email = (new TemplatedEmail())
            ->to(...$participantsMail)
            ->subject(\sprintf($subject, $ownerOf))
            ->htmlTemplate($template)
            ->context([
                'gift' => $gift,
                'ownerOf' => $ownerOf,
                'url' => $this->urlGenerator->generate('app_gift_show', ['id' => $gift->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                ...$context,
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
            GiftPriceChangedEvent::class => 'giftPriceChanged',
        ];
    }
}
