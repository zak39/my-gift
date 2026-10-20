<?php

namespace App\EventSubscriber;

use App\Entity\Gift;
use App\Entity\Participant;
use App\Enum\GiftStatus;
use App\Event\GiftNoLongerReadyEvent;
use App\Event\GiftReadyEvent;
use App\Services\Gift\GiftOwnerLabel;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Event\EnteredEvent;
use Symfony\Component\Workflow\Event\TransitionEvent;

class WorkflowGiftSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        private GiftOwnerLabel $giftOwnerLabel,
    ) {
    }

    public function giftReserved(EnteredEvent $event): void
    {
        /** @var Gift $gift */
        $gift = $event->getSubject();

        $this->sendToAuthor($gift, 'Votre demande de cadeau a été réservée ! 🎁', 'emails/gift_reserved.html.twig', [
            'participant' => $gift->getParticipants()->first(),
        ]);
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

        $this->sendToAuthor($gift, "Mince ! $participantName a retiré sa réservation ! 😢", 'emails/gift_disengaged.html.twig', [
            'participant' => $participant,
        ]);
    }

    public function giftContributing(CompletedEvent $event): void
    {
        /** @var Gift $gift */
        $gift = $event->getSubject();

        $participant = $event->getContext()['participant'];
        $ownerOf = $this->giftOwnerLabel->of($gift);

        $email = (new TemplatedEmail())
            ->to($participant->getParticipantUser()->getEmail())
            ->subject('Vous participez au cadeau '.$ownerOf.' ! 🎁')
            ->context([
                'participant' => $participant,
                'gift' => $gift,
                'ownerName' => $this->giftOwnerLabel->name($gift),
                'ownerOf' => $ownerOf,
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
        $ownerOf = $this->giftOwnerLabel->of($gift);

        $email = (new TemplatedEmail())
            ->to($participant->getParticipantUser()->getEmail())
            ->subject('Vous êtiez le dernier participant au cadeau '.$ownerOf.' ! 😢')
            ->context([
                'participant' => $participant,
                'gift' => $gift,
                'ownerName' => $this->giftOwnerLabel->name($gift),
                'ownerOf' => $ownerOf,
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

        $this->sendToAuthor($gift, "Votre cadeau {$gift->getTitle()} n'est plus prêt", 'emails/gift_no_longer_ready.html.twig', [
            'backOnOffer' => GiftStatus::CREATED === $gift->getStatus(),
        ]);
    }

    public function giftReady(GiftReadyEvent $event): void
    {
        $gift = $event->getGift();

        $this->sendToAuthor($gift, "Votre cadeau {$gift->getTitle()} est prêt ! 🎁", 'emails/gift_ready_for_gift_owner.html.twig');
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

    /**
     * One email to the gift's author, the owner of a personal list, with the gift and its
     * page. A crew gift whose author deleted their account has nobody to tell (its
     * members: #113).
     *
     * @param array<string, mixed> $context added to the template's
     */
    private function sendToAuthor(Gift $gift, string $subject, string $template, array $context = []): void
    {
        $author = $gift->getCreatedBy();

        if (null === $author) {
            return;
        }

        $email = (new TemplatedEmail())
            ->to($author->getEmail())
            ->subject($subject)
            ->htmlTemplate($template)
            ->context([
                'gift' => $gift,
                'url' => $this->urlGenerator->generate('app_gift_show', ['id' => $gift->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                ...$context,
            ])
        ;

        $this->mailer->send($email);
    }
}
