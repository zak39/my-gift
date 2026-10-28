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
use App\Repository\GiftRepository;
use App\Repository\ParticipantRepository;
use App\Services\Exception\GiftActionRefusedException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class GiftLifecycle
{
    public function __construct(
        #[Target('gift_request')]
        private WorkflowInterface $giftRequestWorkflow,
        private EntityManagerInterface $entityManager,
        private EventDispatcherInterface $eventDispatcher,
        private GiftRepository $giftRepository,
        private ParticipantRepository $participantRepository,
        private ValidatorInterface $validator,
    ) {
    }

    public function reserved(Gift $gift, Participant $participant, User $user): void
    {
        $this->whileLocked($gift, function () use ($gift, $participant, $user): void {
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
        });
    }

    public function disengage(Gift $gift, User $user): void
    {
        $this->whileLocked($gift, fn () => $this->withdrawReservation($gift, $user, 'disengage'));
    }

    public function ready(Gift $gift, User $user): void
    {
        $this->whileLocked($gift, function () use ($gift, $user): void {
            // The guard of a pool (isComplete()) sums the amounts as committed.
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
        });
    }

    /**
     * The price of a pool is a target, not a threshold: when it moves, the contributors
     * have to review their share. A gift declared ready was bought, so a new price on a
     * pool reopens it; on a reservation it only updates the information.
     *
     * Called once the new price is saved (UpdateGift): the gift read again under the lock
     * holds it, with the status and the shares others committed meanwhile.
     */
    public function priceChanged(Gift $gift, float $previousPrice): void
    {
        try {
            $this->whileLocked($gift, function () use ($gift, $previousPrice): void {
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
            });
        } catch (GiftActionRefusedException) {
            // The one refusal here: the gift deleted while its price was being edited.
            // Nothing left to reopen nor to announce, and its editor is sent back to the
            // list, where it is gone.
        }
    }

    public function given(Gift $gift): void
    {
        $this->whileLocked($gift, function () use ($gift): void {
            if (!$this->giftRequestWorkflow->can($gift, 'give')) {
                throw new GiftActionRefusedException('Le cadeau n\'est pas encore prêt à être donné.');
            }

            $gift->setGivenAt(new \DateTimeImmutable());
            $this->giftRequestWorkflow->apply($gift, 'give');
            $this->entityManager->flush();
        });
    }

    public function archived(Gift $gift): void
    {
        $this->whileLocked($gift, function () use ($gift): void {
            if (!$this->giftRequestWorkflow->can($gift, 'archive')) {
                throw new GiftActionRefusedException('Le cadeau n\'est pas encore prêt à être archivé.');
            }

            $this->giftRequestWorkflow->apply($gift, 'archive');
            $this->entityManager->flush();
        });
    }

    /** Joins the pool, or changes the share of who already takes part in it. */
    public function participated(Gift $gift, Participant $participant, User $user): void
    {
        $this->whileLocked($gift, function () use ($gift, $participant, $user): void {
            if (GiftStatus::CREATED !== $gift->getStatus()
                && GiftStatus::CONTRIBUTING !== $gift->getStatus()) {
                throw new GiftActionRefusedException('Ce cadeau ne peut pas recevoir de contribution à son état actuel.');
            }

            // The user's participation as committed must be the one the form edited, none for
            // a newcomer: another tab may have joined (a second row for the same user), or
            // withdrawn their share (saving it would update a row gone), or both.
            $current = $gift->getParticipant($user);
            if (null === $participant->getId() && null !== $current) {
                throw new GiftActionRefusedException('Vous participez déjà à ce cadeau, depuis une autre page : vous pouvez modifier votre participation.');
            }
            if (null !== $participant->getId() && null === $current) {
                throw new GiftActionRefusedException('Votre participation a été retirée entre-temps : vous pouvez participer à nouveau.');
            }
            if (null !== $participant->getId() && $current !== $participant) {
                throw new GiftActionRefusedException('Votre participation a changé depuis une autre page : vérifiez-la avant de la modifier.');
            }

            // Checked again against the pool as committed: the form checked the gift as
            // loaded, before another contribution or a new price may have landed.
            $violations = $this->validator->validateProperty($participant, 'amount');
            if (\count($violations) > 0) {
                throw new GiftActionRefusedException((string) $violations->get(0)->getMessage());
            }

            // Before addParticipant(): a changed share must not be announced as a newcomer.
            $joins = !$gift->getParticipants()->contains($participant);

            $participant
                ->setParticipantUser($user)
                ->setStatus(ParticipantStatus::PARTICIPATING)
            ;

            // Not setGift() alone: the event's listeners read the gift's collection.
            $gift->addParticipant($participant);

            if (GiftStatus::CREATED === $gift->getStatus()) {
                $this->giftRequestWorkflow->apply($gift, 'contribute', [
                    'participant' => $participant,
                ]);
            }

            if ($joins) {
                $this->eventDispatcher->dispatch(new ParticipantJoinedEvent($gift, $participant));
            }

            $this->entityManager->persist($participant);
            $this->entityManager->flush();
        }, except: $participant);
    }

    /**
     * @param bool $accountDeleted the giver is deleting their account: they are not told
     *                             they were the last one (WorkflowGiftSubscriber)
     */
    public function cancelledContribution(Gift $gift, User $user, bool $accountDeleted = false): void
    {
        $this->whileLocked($gift, fn () => $this->leavePool($gift, $user, $accountDeleted));
    }

    /**
     * The giver of a reservation they declared ready deletes their account: the gift is
     * on offer again, its owner told as when a reservation is released. No button offers
     * it: ReadyReservationRelease alone calls it.
     */
    public function release(Gift $gift, User $user): void
    {
        $this->whileLocked($gift, fn () => $this->withdrawReservation($gift, $user, 'release'));
    }

    /**
     * A contributor to a pool declared ready deletes their account: the pool reopens
     * without their share, for the others to declare it ready again, or goes back on
     * offer if they were alone. Its owner, told it was ready, is told it no longer is.
     * ReadyPoolRelease calls it.
     */
    public function leaveReadyPool(Gift $gift, User $user): void
    {
        // One lock for both steps: a second one would read the gift again, its reopening
        // not saved yet.
        $this->whileLocked($gift, function () use ($gift, $user): void {
            if (!$this->giftRequestWorkflow->can($gift, 'reopen')) {
                throw new GiftActionRefusedException('Le cadeau n\'est plus prêt à être donné.');
            }

            $this->giftRequestWorkflow->apply($gift, 'reopen');
            $this->leavePool($gift, $user, accountDeleted: true);

            $this->eventDispatcher->dispatch(new GiftNoLongerReadyEvent($gift));
        });
    }

    /**
     * A contribution withdrawn: by its giver (cancelledContribution()), or by a giver
     * deleting their account from a pool open or declared ready (leaveReadyPool()). The
     * last one out puts the gift back on offer.
     */
    private function leavePool(Gift $gift, User $user, bool $accountDeleted): void
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

    /**
     * A gift changes one request at a time: its row locked till the commit, the gift read
     * again with its participations, every transition decides on what the database holds.
     * Else two friends could reserve it at once, two contributions each fit in the
     * remaining amount and together exceed it, or the last contributor leaving put the gift
     * back on offer while someone joins. A DBAL transaction, as CrewLifecycle::whileLocked()
     * says why; within a wider one (an account deletion, UserLifecycle), a savepoint.
     *
     * Anything not flushed on the gift or its participations is lost to the refresh, but
     * $except: a share being changed, which holds what was typed in the form.
     *
     * Public for a decision about the gift taken outside this class: ReleaseParticipation
     * choosing how to release a participation from the gift's status. The transition it
     * then calls locks again, a savepoint reading the same gift once more.
     */
    public function whileLocked(Gift $gift, \Closure $change, ?Participant $except = null): void
    {
        $this->entityManager->getConnection()->transactional(function () use ($gift, $change, $except): void {
            if (null === $this->giftRepository->findOneLocked($gift->getId())) {
                throw new GiftActionRefusedException('Ce cadeau n\'existe plus.');
            }

            $this->participantRepository->refreshIn($gift, $except);

            $change();
        });
    }
}
