<?php

namespace App\EventSubscriber;

use App\Entity\Gift;
use App\Entity\Participant;
use App\Entity\User;
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

        $participant = $gift->getParticipants()->first();

        $this->sendToOwners($gift, 'Votre demande de cadeau a été réservée ! 🎁', 'emails/gift_reserved.html.twig', [
            'participant' => $participant,
        ], excluded: $participant->getParticipantUser());
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

        $this->sendToOwners($gift, "Mince ! $participantName a retiré sa réservation ! 😢", 'emails/gift_disengaged.html.twig', [
            'participant' => $participant,
            // Its case set by the template's |title, as on the other emails.
            'participantName' => $participant->getDisplayName(),
        ], excluded: $participant->getParticipantUser());
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

        $this->sendToOwners($gift, "Votre cadeau {$this->titleWithCrew($gift)} n'est plus prêt", 'emails/gift_no_longer_ready.html.twig', [
            'backOnOffer' => GiftStatus::CREATED === $gift->getStatus(),
        ]);
    }

    public function giftReady(GiftReadyEvent $event): void
    {
        $gift = $event->getGift();

        $this->sendToOwners($gift, "Votre cadeau {$this->titleWithCrew($gift)} est prêt ! 🎁", 'emails/gift_ready_for_gift_owner.html.twig');
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
     * One email to the gift's owners (GiftOwnership): its author for a personal gift, every
     * member of its crew for a crew's, in one To:. Nothing sent when nobody is left to
     * tell: a mail failing here would abort the workflow transition or the account
     * deletion that triggered it.
     *
     * @param array<string, mixed> $context  added to the template's
     * @param ?User                $excluded the giver behind the event, a member of the
     *                                       crew too when they joined it after taking part
     */
    private function sendToOwners(Gift $gift, string $subject, string $template, array $context = [], ?User $excluded = null): void
    {
        $owners = array_filter($gift->getOwnership()->owners(), fn (User $owner) => $owner !== $excluded);

        if (!$owners) {
            return;
        }

        $crew = $gift->getCrew();

        $email = (new TemplatedEmail())
            ->to(...array_map(fn (User $owner) => $owner->getEmail(), $owners))
            ->subject($subject)
            ->htmlTemplate($template)
            ->context([
                'gift' => $gift,
                // Plain values: the worker renders the email later. A crew's members are
                // greeted together, the crew named after the gift (Maizzle's GiftTitle).
                'recipientName' => null !== $crew ? null : $this->giftOwnerLabel->name($gift),
                'crewOf' => null !== $crew ? $this->giftOwnerLabel->of($gift) : null,
                'url' => $this->urlGenerator->generate('app_gift_show', ['id' => $gift->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                ...$context,
            ])
        ;

        $this->mailer->send($email);
    }

    /**
     * « Lego », or « Lego du groupe « La famille » »: a member of several crews tells their
     * gifts apart from the subject.
     */
    private function titleWithCrew(Gift $gift): string
    {
        return null !== $gift->getCrew()
            ? $gift->getTitle().' '.$this->giftOwnerLabel->of($gift)
            : $gift->getTitle()
        ;
    }
}
