<?php

namespace App\Services\Lifecycle;

use App\Entity\Gift;
use App\Entity\Participant;
use App\Entity\User;
use App\Enum\GiftStatus;
use App\Enum\ParticipantStatus;
use App\Event\GiftNoLongerReadyEvent;
use App\Event\GiftPriceChangedEvent;
use App\Event\GiftReadyEvent;
use App\Event\ParticipantJoinedEvent;
use App\Event\ParticipantLeftEvent;
use App\Services\Exception\GiftActionRefusedException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class GiftLifecycle
{
    public function __construct(
        #[Target('gift_request')]
        private WorkflowInterface $giftRequestWorkflow,
        private EntityManagerInterface $entityManager,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function reserved(Gift $gift, Participant $participant, User $user): void
    {
        if (!$this->giftRequestWorkflow->can($gift, 'reserve')) {
            throw new GiftActionRefusedException('Ce cadeau ne peut pas être réservé.');
        }

        $participant
            ->setParticipantUser($user)
            ->setStatus(ParticipantStatus::RESERVED)
        ;

        $gift->addParticipant($participant);

        $this->giftRequestWorkflow->apply($gift, 'reserve');

        $this->entityManager->persist($participant);
        $this->entityManager->flush();
    }

    public function disengage(Gift $gift, User $user): void
    {
        $this->withdrawReservation($gift, $user, 'disengage');
    }

    public function ready(Gift $gift, User $user): void
    {
        if (!$this->giftRequestWorkflow->can($gift, 'finalize')) {
            throw new GiftActionRefusedException('Le cadeau ne peut pas encore être marqué comme prêt à être donné.');
        }

        $participant = $gift->getParticipant($user);

        if (!$participant) {
            throw new GiftActionRefusedException('Aucune participation trouvée pour cet utilisateur sur ce cadeau.');
        }

        $this->giftRequestWorkflow->apply($gift, 'finalize');
        $this->entityManager->flush();

        // A reservation has a single participant: the one who just clicked, nobody to tell.
        if ($gift->getPool()->isPooled()) {
            $this->eventDispatcher->dispatch(new GiftReadyEvent($gift));
        }
    }

    /**
     * The price of a pool is a target, not a threshold: when it moves, the contributors
     * have to review their share. A gift declared ready was bought, so a new price on a
     * pool reopens it; on a reservation it only updates the information.
     */
    public function priceChanged(Gift $gift, float $previousPrice): void
    {
        if (!$gift->getPool()->isPooled()) {
            return;
        }

        if ($this->giftRequestWorkflow->can($gift, 'reopen')) {
            $this->giftRequestWorkflow->apply($gift, 'reopen');
            $this->entityManager->flush();
        }

        if ($gift->getStatus()?->isPoolOpen()) {
            $this->eventDispatcher->dispatch(new GiftPriceChangedEvent($gift, $previousPrice));
        }
    }

    public function given(Gift $gift): void
    {
        if (!$this->giftRequestWorkflow->can($gift, 'give')) {
            throw new GiftActionRefusedException('Le cadeau n\'est pas encore prêt à être donné.');
        }

        $gift->setGivenAt(new \DateTimeImmutable());
        $this->giftRequestWorkflow->apply($gift, 'give');
        $this->entityManager->flush();
    }

    public function archived(Gift $gift): void
    {
        if (!$this->giftRequestWorkflow->can($gift, 'archive')) {
            throw new GiftActionRefusedException('Le cadeau n\'est pas encore prêt à être archivé.');
        }

        $this->giftRequestWorkflow->apply($gift, 'archive');
        $this->entityManager->flush();
    }

    public function participated(Gift $gift, Participant $newParticipant, User $user): void
    {
        if (GiftStatus::CREATED !== $gift->getStatus()
            && GiftStatus::CONTRIBUTING !== $gift->getStatus()) {
            throw new GiftActionRefusedException('Ce cadeau ne peut pas recevoir de contribution à son état actuel.');
        }

        $newParticipant
            ->setParticipantUser($user)
            ->setStatus(ParticipantStatus::PARTICIPATING)
        ;

        // Not setGift() alone: the event's listeners read the gift's collection.
        $gift->addParticipant($newParticipant);

        if (GiftStatus::CREATED === $gift->getStatus()) {
            $this->giftRequestWorkflow->apply($gift, 'contribute', [
                'participant' => $newParticipant,
            ]);
        }

        $this->eventDispatcher->dispatch(new ParticipantJoinedEvent($gift, $newParticipant));

        $this->entityManager->persist($newParticipant);
        $this->entityManager->flush();
    }

    /**
     * @param bool $accountDeleted the giver is deleting their account: they are not told
     *                             they were the last one (WorkflowGiftSubscriber)
     */
    public function cancelledContribution(Gift $gift, User $user, bool $accountDeleted = false): void
    {
        if (!$this->giftRequestWorkflow->can($gift, 'cancelled_contribution')) {
            throw new GiftActionRefusedException('Le cadeau ne peut pas encore être annulé.');
        }

        $participant = $gift->getParticipant($user);
        if (!$participant) {
            throw new GiftActionRefusedException('Aucune participation trouvée pour cet utilisateur sur ce cadeau.');
        }

        $gift->removeParticipant($participant);

        $this->eventDispatcher->dispatch(new ParticipantLeftEvent($gift, $participant));

        if ($gift->getParticipants()->isEmpty()) {
            $this->giftRequestWorkflow->apply($gift, 'cancelled_contribution', [
                'participant' => $participant,
                'accountDeleted' => $accountDeleted,
            ]);
        }

        $this->entityManager->flush();
    }

    /**
     * The giver of a reservation they declared ready deletes their account: the gift is
     * on offer again, its owner told as when a reservation is released. No button offers
     * it: ReadyReservationRelease alone calls it.
     */
    public function release(Gift $gift, User $user): void
    {
        $this->withdrawReservation($gift, $user, 'release');
    }

    /**
     * A contributor to a pool declared ready deletes their account: the pool reopens
     * without their share, for the others to declare it ready again, or goes back on
     * offer if they were alone. Its owner, told it was ready, is told it no longer is.
     * ReadyPoolRelease calls it.
     */
    public function leaveReadyPool(Gift $gift, User $user): void
    {
        $this->giftRequestWorkflow->apply($gift, 'reopen');
        $this->cancelledContribution($gift, $user, accountDeleted: true);

        $this->eventDispatcher->dispatch(new GiftNoLongerReadyEvent($gift));
    }

    /**
     * A reservation given up: released by its giver (`disengage`), or declared ready and
     * left by a giver deleting their account (`release`). Both put the gift back on offer,
     * the participant in the context for its owner's email (WorkflowGiftSubscriber).
     */
    private function withdrawReservation(Gift $gift, User $user, string $transition): void
    {
        if (!$this->giftRequestWorkflow->can($gift, $transition)) {
            throw new GiftActionRefusedException('Le cadeau ne peut pas être libéré dans son état actuel.');
        }

        $participant = $gift->getParticipant($user);
        if (!$participant) {
            throw new GiftActionRefusedException('Aucune participation trouvée pour cet utilisateur sur ce cadeau.');
        }

        $gift->removeParticipant($participant);
        $this->giftRequestWorkflow->apply($gift, $transition, [
            'participant' => $participant,
        ]);
        $this->entityManager->flush();
    }
}
