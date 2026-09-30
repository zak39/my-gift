<?php

namespace App\Services\Lifecycle;

use App\Entity\Gift;
use App\Entity\Participant;
use App\Entity\User;
use App\Enum\GiftStatus;
use App\Enum\ParticipantStatus;
use App\Event\GiftPriceChangedEvent;
use App\Event\GiftReadyEvent;
use App\Event\ParticipantJoinedEvent;
use App\Event\ParticipantLeftEvent;
use App\Repository\ParticipantRepository;
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
        private ParticipantRepository $participantRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    private function getParticipant(Gift $gift, User $user): ?Participant
    {
        return
            $this->participantRepository->findOneBy(
                [
                    'participantUser' => $user,
                    'gift' => $gift,
                ]
            )
        ;
    }

    public function reserved(Gift $gift, Participant $participant, User $user): void
    {
        if (!$this->giftRequestWorkflow->can($gift, 'reserve')) {
            throw new \Exception('Ce cadeau ne peut pas être réservé.');
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
        if (!$this->giftRequestWorkflow->can($gift, 'disengage')) {
            throw new \LogicException('Le cadeau ne peut pas être libéré dans son état actuel.');
        }

        $participant = $this->getParticipant($gift, $user);

        if (!$participant) {
            throw new \LogicException('Aucune participation trouvée pour cet utilisateur sur ce cadeau.');
        }

        $gift->removeParticipant($participant);
        $this->giftRequestWorkflow->apply($gift, 'disengage', [
            'participant' => $participant,
        ]);
        $this->entityManager->flush();
    }

    public function ready(Gift $gift, User $user): void
    {
        if (!$this->giftRequestWorkflow->can($gift, 'finalize')) {
            throw new \LogicException('Le cadeau ne peut pas encore être marqué comme prêt à être donné.');
        }

        $participant = $this->getParticipant($gift, $user);

        if (!$participant) {
            throw new \LogicException('Aucune participation trouvée pour cet utilisateur sur ce cadeau.');
        }

        $this->giftRequestWorkflow->apply($gift, 'finalize');
        $this->entityManager->flush();

        // A reservation has a single participant: the one who just clicked, nobody to tell.
        if ($gift->isPooled()) {
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
        if (!$gift->isPooled()) {
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
            throw new \LogicException('Le cadeau n\'est pas encore prêt à être donné.');
        }

        $gift->setGivenAt(new \DateTimeImmutable());
        $this->giftRequestWorkflow->apply($gift, 'give');
        $this->entityManager->flush();
    }

    public function archived(Gift $gift): void
    {
        if (!$this->giftRequestWorkflow->can($gift, 'archive')) {
            throw new \LogicException('Le cadeau n\'est pas encore prêt à être archivé.');
        }

        $this->giftRequestWorkflow->apply($gift, 'archive');
        $this->entityManager->flush();
    }

    public function participated(Gift $gift, Participant $newParticipant, User $user): void
    {
        if (GiftStatus::CREATED !== $gift->getStatus()
            && GiftStatus::CONTRIBUTING !== $gift->getStatus()) {
            throw new \Exception('Ce cadeau ne peut pas recevoir de contribution à son état actuel.');
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

    public function cancelledContribution(Gift $gift, User $user): void
    {
        if (!$this->giftRequestWorkflow->can($gift, 'cancelled_contribution')) {
            throw new \LogicException('Le cadeau ne peut pas encore être annulé.');
        }

        $participant = $this->getParticipant($gift, $user);
        if (!$participant) {
            throw new \LogicException('Aucune participation trouvée pour cet utilisateur sur ce cadeau.');
        }

        $gift->removeParticipant($participant);

        $this->eventDispatcher->dispatch(new ParticipantLeftEvent($gift, $participant));

        if ($gift->getParticipants()->isEmpty()) {
            $this->giftRequestWorkflow->apply($gift, 'cancelled_contribution', [
                'participant' => $participant,
            ]);
        }

        $this->entityManager->flush();
    }
}
