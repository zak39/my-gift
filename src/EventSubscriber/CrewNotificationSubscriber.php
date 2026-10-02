<?php

namespace App\EventSubscriber;

use App\Entity\Crew;
use App\Entity\User;
use App\Event\CrewDeletedEvent;
use App\Event\CrewInvitationSentEvent;
use App\Event\CrewMemberJoinedEvent;
use App\Event\CrewMemberLeftEvent;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Tells the people concerned when someone is invited into a group, joins it, leaves it,
 * or when its creator deletes it. The sender is the mailer's (config/packages/mailer.yaml).
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
        $this->tellOtherMembers($event->getCrew(), $event->getMember(), '%s a rejoint le groupe « %s » 🎉', 'emails/crew_member_joined.html.twig');
    }

    public function memberLeft(CrewMemberLeftEvent $event): void
    {
        $this->tellOtherMembers($event->getCrew(), $event->getMember(), '%s a quitté le groupe « %s »', 'emails/crew_member_left.html.twig');
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
                    'url' => $this->urlGenerator->generate('app_section', ['id' => $member->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
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
        ];
    }

    /**
     * One email to the other members about the one who joined or left, with their card.
     * There is always someone to tell: the creator never leaves, and joining makes two.
     *
     * @param string $subject a sprintf() pattern receiving the member's first name and the crew's name
     */
    private function tellOtherMembers(Crew $crew, User $member, string $subject, string $template): void
    {
        $email = (new TemplatedEmail())
            ->to(...array_map(fn (User $user) => $user->getEmail(), $crew->getMembersExcept($member)))
            ->subject(\sprintf($subject, ucfirst($member->getFirstname()), $crew->getName()))
            ->htmlTemplate($template)
            ->context([
                'crewName' => $crew->getName(),
                'member' => $member,
                'url' => $this->urlGenerator->generate('app_crew_show', ['id' => $crew->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            ])
        ;

        $this->mailer->send($email);
    }
}
