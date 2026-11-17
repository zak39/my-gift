<?php

namespace App\EventSubscriber;

use App\Entity\Crew;
use App\Entity\Participant;
use App\Entity\User;
use App\Event\CrewDeletedEvent;
use App\Event\CrewGiftDeletedEvent;
use App\Event\CrewHandedOverEvent;
use App\Event\CrewInvitationSentEvent;
use App\Event\CrewMemberJoinedEvent;
use App\Event\CrewMemberLeftEvent;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Tells the people concerned when someone is invited into a group, joins it, leaves it,
 * when its owner deletes it, or passes it on to another member. The sender is the mailer's (config/packages/mailer.yaml).
 *
 * The contexts hold plain values and the users the cards show, not the crew: the email is
 * serialized into the queue and rendered by the worker, sometimes once the crew is gone.
 */
class CrewNotificationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function invitationSent(CrewInvitationSentEvent $event): void
    {
        $invitation = $event->getInvitation();
        $inviter = $invitation->getInvitedBy();
        $crewName = $invitation->getCrew()->getName();

        $email = (new TemplatedEmail())
            ->to($invitation->getInvitee()->getEmail())
            ->subject(\sprintf('%s vous invite à rejoindre le groupe « %s » 🎁', $inviter->getPseudo(), $crewName))
            ->htmlTemplate('emails/crew_invitation.html.twig')
            ->context([
                'crewName' => $crewName,
                'inviter' => $inviter,
                'invitee' => $invitation->getInvitee(),
                // The invitations wait on « Mes groupes », with their answer buttons.
                'url' => $this->urlGenerator->generate('app_crew', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ])
        ;

        $this->mailer->send($email);
    }

    public function memberJoined(CrewMemberJoinedEvent $event): void
    {
        $crew = $event->getCrew();
        $member = $event->getMember();

        $this->tellMembers($crew->getMembersExcept($member), $crew, $member, '%s a rejoint le groupe « %s » 🎉', 'emails/crew_member_joined.html.twig');
    }

    public function memberLeft(CrewMemberLeftEvent $event): void
    {
        $crew = $event->getCrew();

        $this->tellMembers(
            $crew->getMembers(),
            $crew,
            $event->getMember(),
            '%s a quitté le groupe « %s »',
            'emails/crew_member_left.html.twig',
            // No card for a deleted account: its avatar and profile are gone.
            ['accountDeleted' => $event->isAccountDeleted()],
        );
    }

    /**
     * The new owner learns they took the crew over. Passed on by its owner, nothing
     * changes for the others: they see it on the crew's page. On an account deletion,
     * they learn the previous owner left and who owns the crew now. One email each.
     */
    public function crewHandedOver(CrewHandedOverEvent $event): void
    {
        $crew = $event->getCrew();
        $newOwner = $crew->getOwner();
        $previousOwner = $event->getPreviousOwner();

        $email = (new TemplatedEmail())
            ->to($newOwner->getEmail())
            ->subject(\sprintf('Vous êtes maintenant propriétaire du groupe « %s »', $crew->getName()))
            ->htmlTemplate('emails/crew_handed_over.html.twig')
            ->context([
                'crewName' => $crew->getName(),
                // A name only: on an account deletion, theirs is about to go.
                'previousOwnerName' => $previousOwner->getPseudo(),
                'newOwner' => $newOwner,
                'accountDeleted' => $event->isAccountDeleted(),
                'url' => $this->urlGenerator->generate('app_crew_show', ['id' => $crew->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            ])
        ;

        $this->mailer->send($email);

        if (!$event->isAccountDeleted()) {
            return;
        }

        $this->tellMembers(
            $crew->getMembersExcept($newOwner),
            $crew,
            $previousOwner,
            '%s a quitté le groupe « %s »',
            'emails/crew_member_left.html.twig',
            // Past the return above: the previous owner deleted their account.
            ['accountDeleted' => true, 'newOwnerName' => $newOwner->getPseudo()],
        );
    }

    /**
     * One email per member but the owner, who deleted it, with a link to their own list,
     * where their gifts are back.
     */
    public function crewDeleted(CrewDeletedEvent $event): void
    {
        $owner = $event->getOwner();

        foreach ($event->getMembers() as $member) {
            if ($member === $owner) {
                continue;
            }

            $email = (new TemplatedEmail())
                ->to($member->getEmail())
                ->subject(\sprintf('Le groupe « %s » a été supprimé', $event->getCrewName()))
                ->htmlTemplate('emails/crew_deleted.html.twig')
                ->context([
                    'crewName' => $event->getCrewName(),
                    'owner' => $owner,
                    'member' => $member,
                    'url' => $this->urlGenerator->generate('app_section', [], UrlGeneratorInterface::ABSOLUTE_URL),
                ])
            ;

            $this->mailer->send($email);
        }
    }

    /**
     * One email to the givers of a crew gift that went with its section or its crew, its
     * author gone, as when a gift goes with its owner's account (AccountDeletionSubscriber).
     */
    public function crewGiftDeleted(CrewGiftDeletedEvent $event): void
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

        $email = (new TemplatedEmail())
            // In blind copy: the givers are friends of the owner, or of a crew member, not
            // always of each other, and nobody's address shows to the others.
            ->bcc(...array_map(fn (Participant $participant) => $participant->getParticipantUser()->getEmail(), $recipients))
            ->subject(\sprintf('Le cadeau « %s » du groupe « %s » n\'existe plus', $gift->getTitle(), $event->getCrewName()))
            ->htmlTemplate('emails/crew_gift_deleted.html.twig')
            ->context([
                'giftTitle' => $gift->getTitle(),
                'crewName' => $event->getCrewName(),
                'crewDeleted' => $event->isCrewDeleted(),
                // A ready gift may come from a reservation or from a pool.
                'pooled' => $gift->getPool()->isPooled(),
            ])
        ;

        $this->mailer->send($email);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CrewGiftDeletedEvent::class => 'crewGiftDeleted',
            CrewInvitationSentEvent::class => 'invitationSent',
            CrewMemberJoinedEvent::class => 'memberJoined',
            CrewMemberLeftEvent::class => 'memberLeft',
            CrewDeletedEvent::class => 'crewDeleted',
            CrewHandedOverEvent::class => 'crewHandedOver',
        ];
    }

    /**
     * One email to the members about the one who joined or left, with their card. A crew
     * handed over may leave nobody to tell but its new owner, told apart.
     *
     * @param list<User>           $recipients
     * @param string               $subject    a sprintf() pattern receiving the member's pseudo and the crew's name
     * @param array<string, mixed> $context    added to the template's
     */
    private function tellMembers(array $recipients, Crew $crew, User $member, string $subject, string $template, array $context = []): void
    {
        if (!$recipients) {
            return;
        }

        $email = (new TemplatedEmail())
            ->to(...array_map(fn (User $user) => $user->getEmail(), $recipients))
            ->subject(\sprintf($subject, $member->getPseudo(), $crew->getName()))
            ->htmlTemplate($template)
            ->context([
                'crewName' => $crew->getName(),
                'member' => $member,
                'url' => $this->urlGenerator->generate('app_crew_show', ['id' => $crew->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                ...$context,
            ])
        ;

        $this->mailer->send($email);
    }
}
