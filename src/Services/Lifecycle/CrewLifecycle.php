<?php

namespace App\Services\Lifecycle;

use App\Entity\Crew;
use App\Entity\CrewInvitation;
use App\Entity\User;
use App\Event\CrewDeletedEvent;
use App\Event\CrewHandedOverEvent;
use App\Event\CrewInvitationSentEvent;
use App\Event\CrewMemberJoinedEvent;
use App\Event\CrewMemberLeftEvent;
use App\Repository\FriendBookRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Every change to who is in a crew goes through here, as FriendRequestLifecycle does for
 * friendships. The workflow guards check who may answer or cancel an invitation against
 * the logged-in user: for anyone else can() is false, and the method throws.
 */
class CrewLifecycle
{
    public function __construct(
        #[Target('crew_invitation')]
        private WorkflowInterface $crewInvitationWorkflow,
        private EntityManagerInterface $entityManager,
        private FriendBookRepository $friendBookRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function invite(Crew $crew, User $invitedBy, User $invitee): void
    {
        // A crew gathers people who already know each other: members invite their friends.
        if (!$this->friendBookRepository->of($invitedBy)->hasFriend($invitee)) {
            throw new \LogicException('Vous ne pouvez inviter que vos ami·es.');
        }

        $invitation = $crew->invite($invitedBy, $invitee);
        $this->entityManager->persist($invitation);

        try {
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            // Another member invited them in the meantime: Crew::invite() could not see it.
            throw new \LogicException(\sprintf('%s a déjà été invité·e dans le groupe.', $invitee->getFirstname()));
        }

        $this->eventDispatcher->dispatch(new CrewInvitationSentEvent($invitation));
    }

    public function accept(CrewInvitation $invitation): void
    {
        $this->applyTransition($invitation, 'accept', 'Cette invitation ne peut pas être acceptée.');

        $invitation->getCrew()->addMember($invitation->getInvitee());

        // The invitee is a member from now on: the invitation has done its job.
        $this->entityManager->remove($invitation);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(new CrewMemberJoinedEvent($invitation->getCrew(), $invitation->getInvitee()));
    }

    public function refuse(CrewInvitation $invitation): void
    {
        $this->applyTransition($invitation, 'refuse', 'Cette invitation ne peut pas être refusée.');
        $this->entityManager->remove($invitation);
        $this->entityManager->flush();
    }

    public function cancel(CrewInvitation $invitation): void
    {
        $this->applyTransition($invitation, 'cancel', 'Cette invitation ne peut pas être annulée.');
        $this->entityManager->remove($invitation);
        $this->entityManager->flush();
    }

    /**
     * The only way out of a crew for a member: nobody takes anyone else out. The gifts
     * they added stay in the crew's list. The owner cannot leave (Crew::removeMember()).
     */
    public function leave(Crew $crew, User $user): void
    {
        $this->removeMember($crew, $user, accountDeleted: false);
    }

    /**
     * A member deleting their account: the others are told they left, as for leave(), but
     * without the card that would lead to a profile about to disappear.
     */
    public function leaveOnAccountDeletion(Crew $crew, User $user): void
    {
        $this->removeMember($crew, $user, accountDeleted: true);
    }

    /**
     * The owner passing the crew on to another member, who alone may delete it from now
     * on. The previous owner stays a member, free to leave it.
     */
    public function transfer(Crew $crew, User $newOwner): void
    {
        $this->whileLocked($crew, function () use ($crew, $newOwner): void {
            $previousOwner = $crew->getOwner();

            $crew->transferTo($newOwner);
            $this->entityManager->flush();

            $this->eventDispatcher->dispatch(new CrewHandedOverEvent($crew, $previousOwner, accountDeleted: false));
        });
    }

    /**
     * The owner deleting their account: their successor takes the crew over, and they
     * leave it. One event tells everyone, the successor and the other members each their
     * own way.
     */
    public function transferOnAccountDeletion(Crew $crew, User $successor): void
    {
        $this->whileLocked($crew, function () use ($crew, $successor): void {
            $previousOwner = $crew->getOwner();

            $crew->transferTo($successor);
            // No longer its owner: removeMember() lets them go.
            $crew->removeMember($previousOwner);
            $this->entityManager->flush();

            $this->eventDispatcher->dispatch(new CrewHandedOverEvent($crew, $previousOwner, accountDeleted: true));
        });
    }

    /**
     * The owner's way out, which ends the crew for every member. Each gift goes back to
     * its author, among their gifts without a section, as when a section is deleted: a
     * reservation or a pool on it survives the crew.
     */
    public function delete(Crew $crew): void
    {
        // Read before the crew goes, for whoever listens to its deletion.
        $members = $crew->getMembers();

        foreach ($crew->getSections() as $section) {
            $section->releaseGifts();
            $this->entityManager->remove($section);
        }

        // Its pending invitations go with it: ON DELETE CASCADE.
        $this->entityManager->remove($crew);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(new CrewDeletedEvent($crew->getName(), $crew->getOwner(), $members));
    }

    private function removeMember(Crew $crew, User $user, bool $accountDeleted): void
    {
        $this->whileLocked($crew, function () use ($crew, $user, $accountDeleted): void {
            $crew->removeMember($user);
            $this->entityManager->flush();

            $this->eventDispatcher->dispatch(new CrewMemberLeftEvent($crew, $user, $accountDeleted));
        });
    }

    /**
     * Who owns the crew and who is in it change one request at a time: its row locked till
     * the commit, the crew read again, Crew's rules check what the database holds. Else a
     * member could leave while being made owner, and the crew be left with an owner out
     * of it. Anything not flushed on the crew is lost to the refresh.
     *
     * DBAL's transaction, not the ORM's wrapInTransaction(): that one closes the entity
     * manager on any exception, a refusal of Crew's included, for the rest of the request.
     * The emails queued on the way (Messenger's doctrine transport) commit with the change.
     */
    private function whileLocked(Crew $crew, \Closure $change): void
    {
        $this->entityManager->getConnection()->transactional(function () use ($crew, $change): void {
            $this->entityManager->refresh($crew, LockMode::PESSIMISTIC_WRITE);
            $change();
        });
    }

    private function applyTransition(CrewInvitation $invitation, string $transition, string $error): void
    {
        if (!$this->crewInvitationWorkflow->can($invitation, $transition)) {
            throw new \LogicException($error);
        }

        $this->crewInvitationWorkflow->apply($invitation, $transition);
    }
}
