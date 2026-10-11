<?php

namespace App\Services\Lifecycle;

use App\Entity\User;
use App\Event\AccountDeletedEvent;
use App\Event\GiftDeletedWithAccountEvent;
use App\Repository\CrewRepository;
use App\Repository\GiftRepository;
use App\Repository\SectionRepository;
use App\Services\Gift\HandOverGift;
use App\Services\Participation\ReleaseParticipation;
use App\Services\Section\DeleteSection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Deleting an account, from the user's profile or, later, from the purge of inactive
 * accounts: nothing they took part in is left inconsistent, and no crew loses its list.
 *
 * The foreign keys left without ON DELETE (participant, crew, section, gift) are the
 * safety net: a case forgotten here makes the database refuse the deletion instead of
 * leaving a crew without creator or a participation without anyone.
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
        private HandOverGift $handOverGift,
        private DeleteSection $deleteSection,
        private Filesystem $filesystem,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger,
        // Where UserAvatarDirectoryNamer and UserGiftDirectoryNamer file a user's images.
        #[Autowire('%kernel.project_dir%/public/data/users')]
        private string $usersDirectory,
    ) {
    }

    public function delete(User $user): void
    {
        // Read now: Doctrine forgets the id of a deleted entity. Without one, the folder
        // below would be the parent of every user's.
        $id = $user->getId() ?? throw new \LogicException('Only a saved account can be deleted.');
        $folder = $this->usersDirectory.'/'.$id;

        // All or nothing: the services called flush along the way, and the emails they
        // queue (Messenger's doctrine transport) are rolled back with the rest.
        $this->entityManager->wrapInTransaction(function () use ($user): void {
            $this->releaseParticipations($user);
            $this->deleteCrewsWhereAlone($user);
            // Each crew left once, with a single email: as its creator, or as a member.
            $this->handOverCreatedCrews($user);
            // Once every crew has its creator for good: their items go to them.
            $this->handOverFiledItems($user);
            $this->leaveCrews($user);
            $this->deleteOwnSections($user);
            $this->deleteOwnGifts($user);

            // Within the transaction: no confirmation is queued for a deletion rolled back.
            $this->eventDispatcher->dispatch(new AccountDeletedEvent($user->getEmail(), $user->getFirstname()));
            $this->entityManager->remove($user);
        });

        // A deleted file cannot be rolled back: it goes once the database has agreed. From
        // here on, the account is gone: a folder left behind is logged, not reported as a
        // deletion that failed.
        try {
            $this->filesystem->remove($folder);
        } catch (IOExceptionInterface $exception) {
            $this->logger->error('Folder of a deleted account left behind', ['folder' => $folder, 'exception' => $exception]);
        }
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
     * The crews the user created and nobody else joined go with them. Their gifts go back
     * to the user, among their gifts without a section, to be deleted with their own.
     */
    private function deleteCrewsWhereAlone(User $user): void
    {
        foreach ($this->crewRepository->findBy(['createdBy' => $user]) as $crew) {
            if (null === $crew->getSuccessor()) {
                $this->crewLifecycle->delete($crew);
            }
        }
    }

    /**
     * The crews the user created that still exist go on under their oldest member, and
     * the user leaves them.
     */
    private function handOverCreatedCrews(User $user): void
    {
        foreach ($this->crewRepository->findBy(['createdBy' => $user]) as $crew) {
            // Always someone: the crews they were alone in are already deleted.
            $successor = $crew->getSuccessor() ?? throw new \LogicException('A crew left alone should be deleted first.');

            $this->crewLifecycle->handOver($crew, $successor);
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
     * What the user filed in a crew's list stays there, under its creator's name: crews
     * they already left included, since leaving kept their name on it. Two separate reads:
     * their section may hold other members' gifts, and another member's section theirs.
     */
    private function handOverFiledItems(User $user): void
    {
        foreach ($this->sectionRepository->findInCrewsCreatedBy($user) as $section) {
            $section->setCreatedBy($section->getCrew()->getCreatedBy());
        }

        foreach ($this->giftRepository->findInCrewsCreatedBy($user) as $gift) {
            // Its original image goes with the user's folder, once deleted.
            ($this->handOverGift)($gift, $gift->getCrew()->getCreatedBy());
        }

        // One flush for every section and gift handed over: HandOverGift leaves it to us.
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
