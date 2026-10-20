<?php

namespace App\Services\Lifecycle;

use App\Entity\User;
use App\Enum\AccountDeletionReason;
use App\Event\AccountDeletedEvent;
use App\Event\GiftDeletedWithAccountEvent;
use App\Repository\CrewRepository;
use App\Repository\GiftRepository;
use App\Repository\SectionRepository;
use App\Services\Participation\ReleaseParticipation;
use App\Services\PendingFileRemovals;
use App\Services\Section\DeleteSection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Deleting an account, from the user's profile, by an admin or by the purge of inactive
 * accounts (app:users:purge-inactive): nothing they took part in is left inconsistent, and no crew loses its list.
 *
 * The foreign keys left without ON DELETE (participant, crew, section, gift) are the
 * safety net: a case forgotten here makes the database refuse the deletion instead of
 * leaving a crew without owner or a participation without anyone. A crew's creator is
 * history only: ON DELETE SET NULL.
 *
 * What is left to delete is read from the repositories, not from the user's collections:
 * an entity deleted along the way and still held by one of them would make the next flush
 * take it for a new one.
 */
class UserLifecycle
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ReleaseParticipation $releaseParticipation,
        private CrewLifecycle $crewLifecycle,
        private CrewRepository $crewRepository,
        private GiftRepository $giftRepository,
        private SectionRepository $sectionRepository,
        private DeleteSection $deleteSection,
        private PendingFileRemovals $pendingFileRemovals,
        private EventDispatcherInterface $eventDispatcher,
        // Where UserAvatarDirectoryNamer and GiftDirectoryNamer file a user's images.
        #[Autowire('%app.data_dir%/users')]
        private string $usersDirectory,
    ) {
    }

    public function delete(User $user, AccountDeletionReason $reason = AccountDeletionReason::Requested): void
    {
        // Read now: Doctrine forgets the id of a deleted entity. Without one, the folder
        // below would be the parent of every user's.
        $id = $user->getId() ?? throw new \LogicException('Only a saved account can be deleted.');
        $folder = $this->usersDirectory.'/'.$id;

        // All or nothing: the services called flush along the way, and the emails they
        // queue (Messenger's doctrine transport) are rolled back with the rest.
        try {
            $this->entityManager->wrapInTransaction(function () use ($user, $reason): void {
                $this->releaseParticipations($user);
                $this->deleteCrewsWhereAlone($user);
                // Each crew left once, with a single email: as its owner, or as a member.
                $this->handOverOwnedCrews($user);
                // Once every crew has its owner for good: their items go to them.
                $this->handOverFiledItems($user);
                $this->leaveCrews($user);
                $this->deleteOwnSections($user);
                $this->deleteOwnGifts($user);

                // Within the transaction: no confirmation is queued for a deletion rolled back.
                $this->eventDispatcher->dispatch(new AccountDeletedEvent($user->getEmail(), $user->getFirstname(), $reason));
                $this->entityManager->remove($user);
            });
        } catch (\Throwable $exception) {
            // Rolled back: the images moved and the crews deleted on the way are back.
            $this->pendingFileRemovals->forget();

            throw $exception;
        }

        // A deleted file cannot be rolled back: it goes once the database has agreed, with
        // what the flushes inside the transaction left pending (the folders of the crews
        // deleted along). From here on, the account is gone: a folder left behind is
        // logged, not reported as a deletion that failed (PendingFileRemovals).
        $this->pendingFileRemovals->add($folder);
        $this->pendingFileRemovals->removeAll();
    }

    /**
     * What becomes of the user's part in other people's gifts. The rules, one per case,
     * live in src/Services/Participation (App\Strategy\ParticipationReleaseStrategy).
     *
     * Read from the user's collection, unlike the other steps, and kept in step with it:
     * whoever calls delete() may have loaded it already, and a released participation left
     * in it would make the next flush take it for a new one.
     */
    private function releaseParticipations(User $user): void
    {
        foreach ($user->getParticipations()->toArray() as $participation) {
            ($this->releaseParticipation)($participation);
            $user->removeParticipation($participation);
        }
    }

    /**
     * The crews the user owns and nobody else is in go with them. Their gifts go back to
     * their authors, among their gifts without a section: the user's, to be deleted with
     * their own, and those of members who left.
     */
    private function deleteCrewsWhereAlone(User $user): void
    {
        foreach ($this->crewRepository->findBy(['owner' => $user]) as $crew) {
            if (null === $crew->getSuccessor()) {
                $this->crewLifecycle->delete($crew);
            }
        }
    }

    /**
     * The crews the user owns that still exist go on under their oldest member, and the
     * user leaves them.
     */
    private function handOverOwnedCrews(User $user): void
    {
        foreach ($this->crewRepository->findBy(['owner' => $user]) as $crew) {
            // Always someone: the crews they were alone in are already deleted.
            $successor = $crew->getSuccessor() ?? throw new \LogicException('A crew left alone should be deleted first.');

            $this->crewLifecycle->transferOnAccountDeletion($crew, $successor);
        }
    }

    /**
     * Out of the crews where the user is a member only, the others are told they left, as
     * when a member leaves on their own.
     */
    private function leaveCrews(User $user): void
    {
        foreach ($this->crewRepository->findForMember($user) as $crew) {
            $this->crewLifecycle->leaveOnAccountDeletion($crew, $user);
        }
    }

    /**
     * What the user filed in a crew's list stays there, under its owner's name: crews
     * they already left included, since leaving kept their name on it. Two separate reads:
     * their section may hold other members' gifts, and another member's section theirs.
     */
    private function handOverFiledItems(User $user): void
    {
        foreach ($this->sectionRepository->findInCrewsCreatedBy($user) as $section) {
            $section->setCreatedBy($section->getCrew()->getOwner());
        }

        // Their images stay put: GiftDirectoryNamer files a crew gift under its crew.
        foreach ($this->giftRepository->findInCrewsCreatedBy($user) as $gift) {
            $gift->setCreatedBy($gift->getCrew()->getOwner());
        }

        $this->entityManager->flush();
    }

    /**
     * The sections of their own list. Their gifts go among the gifts without a section,
     * deleted next.
     */
    private function deleteOwnSections(User $user): void
    {
        foreach ($this->sectionRepository->findPersonalOf($user) as $section) {
            ($this->deleteSection)($section);
        }
    }

    /**
     * Every gift still theirs once the crews are left, those released by a crew deleted
     * with them included. A gift's participants go with it (Gift::$participants,
     * orphanRemoval): they are told first.
     *
     * Removed without a flush, unlike DeleteGift: Vich deletes an image on the flush that
     * removes its gift. Left to the last flush, the one wrapInTransaction() makes before
     * committing, no image goes if an earlier step fails and rolls everything back.
     */
    private function deleteOwnGifts(User $user): void
    {
        foreach ($this->giftRepository->findBy(['createdBy' => $user]) as $gift) {
            $this->eventDispatcher->dispatch(new GiftDeletedWithAccountEvent($gift));
            $this->entityManager->remove($gift);
        }
    }
}
