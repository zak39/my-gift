<?php

namespace App\EventSubscriber;

use App\Entity\Gift;
use App\Entity\Participant;
use App\Entity\User;
use App\Enum\GiftStatus;
use App\Event\GiftDeletedEvent;
use App\Event\GiftNoLongerReadyEvent;
use App\Event\GiftReadyEvent;
use App\Services\Gift\GiftOwnerLabel;
use App\Services\Url\AvatarUrlGenerator;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Event\EnteredEvent;
use Symfony\Component\Workflow\Event\TransitionEvent;
use Vich\UploaderBundle\Storage\StorageInterface;

class WorkflowGiftSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        private GiftOwnerLabel $giftOwnerLabel,
        private StorageInterface $storage,
        private AvatarUrlGenerator $avatarUrlGenerator,
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

    /**
     * One email to all the givers of a gift its owners deleted, as when it goes with its
     * author's account (AccountDeletionSubscriber) or with a crew's section
     * (CrewNotificationSubscriber). The worker renders it once the gift is gone: the Gift
     * travels serialized with the message, its own fields with it, but its crew is worded
     * now, its image, removed from the disk on flush, is embedded now, and whoever deleted
     * it is told in plain values, without a link the reader may not be allowed to follow.
     */
    public function giftDeleted(GiftDeletedEvent $event): void
    {
        $gift = $event->getGift();
        $deletedBy = $event->getDeletedBy();

        if (!$gift->getStatus()->isInProgress()) {
            return;
        }

        // A member who took part before joining the crew knows they deleted it.
        $recipients = array_filter($gift->getParticipantsToNotify(), fn (Participant $participant) => $participant->getParticipantUser() !== $deletedBy);
        if (!$recipients) {
            return;
        }

        $ownerOf = $this->giftOwnerLabel->of($gift);

        $email = (new TemplatedEmail())
            ->to(...array_map(fn (Participant $participant) => $participant->getParticipantUser()->getEmail(), $recipients))
            ->subject(\sprintf('Le cadeau « %s » %s n\'existe plus', $gift->getTitle(), $ownerOf))
            ->htmlTemplate('emails/gift_deleted.html.twig')
        ;

        $giftImage = null;
        $imagePath = $this->storage->resolvePath($gift, 'imageFile');
        if (null !== $imagePath && is_readable($imagePath)) {
            // Its contents, not its path: the file is gone when the worker sends the email.
            $email->embed(file_get_contents($imagePath), 'gift-image', mime_content_type($imagePath));
            $giftImage = 'cid:gift-image';
        }

        $email->context([
            'gift' => $gift,
            // Null from the admin: the email names My Gift's team.
            'deleter' => null === $deletedBy ? null : [
                'name' => $deletedBy->getFullname(),
                'avatar' => $this->avatarUrlGenerator->imageUrl($deletedBy),
                // A crew's givers may only be friends of another member: their list is not
                // this member's to show them (user.can_see_gifts).
                'profileUrl' => null !== $gift->getCrew() ? null : $this->urlGenerator->generate('app_section_user_page', ['id' => $deletedBy->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            ],
            'ownerOf' => $ownerOf,
            'crewOf' => null !== $gift->getCrew() ? $ownerOf : null,
            'giftImage' => $giftImage,
            // A ready gift may come from a reservation or from a pool.
            'pooled' => $gift->getPool()->isPooled(),
        ]);

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
            GiftDeletedEvent::class => 'giftDeleted',
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
