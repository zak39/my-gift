<?php

namespace App\EventSubscriber;

use App\Entity\Crew;
use App\Entity\User;
use App\Event\CrewDeletedEvent;
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
 * when its creator deletes it, or hands it over on deleting their account. The sender is the mailer's (config/packages/mailer.yaml).
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
            ->subject(\sprintf('%s vous invite à rejoindre le groupe « %s » 🎁', ucfirst($inviter->getFirstname()), $crewName))
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
     * The new creator learns they took the crew over; the other members, that the previous
     * one left and who leads the crew now. One email each.
     */
    public function crewHandedOver(CrewHandedOverEvent $event): void
    {
        $crew = $event->getCrew();
        $newCreator = $crew->getCreatedBy();
        $previousCreator = $event->getPreviousCreator();

        $email = (new TemplatedEmail())
            ->to($newCreator->getEmail())
            ->subject(\sprintf('Vous êtes maintenant créateur·rice du groupe « %s »', $crew->getName()))
            ->htmlTemplate('emails/crew_handed_over.html.twig')
            ->context([
                'crewName' => $crew->getName(),
                // A name only: their account is about to be deleted.
                'previousCreatorName' => $previousCreator->getFullname(),
                'newCreator' => $newCreator,
                'url' => $this->urlGenerator->generate('app_crew_show', ['id' => $crew->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            ])
        ;

        $this->mailer->send($email);

        $this->tellMembers(
            $crew->getMembersExcept($newCreator),
            $crew,
            $previousCreator,
            '%s a quitté le groupe « %s »',
            'emails/crew_member_left.html.twig',
            // handOver() only runs for a creator deleting their account.
            ['accountDeleted' => true, 'newCreatorName' => $newCreator->getFullname()],
        );
    }

    /**
     * One email per member but the creator, who deleted it, with a link to their own list,
     * where their gifts are back.
     */
    public function crewDeleted(CrewDeletedEvent $event): void
    {
        $creator = $event->getCreator();

        foreach ($event->getMembers() as $member) {
            if ($member === $creator) {
                continue;
            }

            $email = (new TemplatedEmail())
                ->to($member->getEmail())
                ->subject(\sprintf('Le groupe « %s » a été supprimé', $event->getCrewName()))
                ->htmlTemplate('emails/crew_deleted.html.twig')
                ->context([
                    'crewName' => $event->getCrewName(),
                    'creator' => $creator,
                    'member' => $member,
                    'url' => $this->urlGenerator->generate('app_section', [], UrlGeneratorInterface::ABSOLUTE_URL),
                ])
            ;

            $this->mailer->send($email);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CrewInvitationSentEvent::class => 'invitationSent',
            CrewMemberJoinedEvent::class => 'memberJoined',
            CrewMemberLeftEvent::class => 'memberLeft',
            CrewDeletedEvent::class => 'crewDeleted',
            CrewHandedOverEvent::class => 'crewHandedOver',
        ];
    }

    /**
     * One email to the members about the one who joined or left, with their card. A crew
     * handed over may leave nobody to tell but its new creator, told apart.
     *
     * @param list<User>           $recipients
     * @param string               $subject    a sprintf() pattern receiving the member's first name and the crew's name
     * @param array<string, mixed> $context    added to the template's
     */
    private function tellMembers(array $recipients, Crew $crew, User $member, string $subject, string $template, array $context = []): void
    {
        if (!$recipients) {
            return;
        }

        $email = (new TemplatedEmail())
            ->to(...array_map(fn (User $user) => $user->getEmail(), $recipients))
            ->subject(\sprintf($subject, ucfirst($member->getFirstname()), $crew->getName()))
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
