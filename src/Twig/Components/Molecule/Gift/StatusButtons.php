<?php

namespace App\Twig\Components\Molecule\Gift;

use App\Entity\Gift;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * The buttons that move a gift along, one getter each, so the template asks a single
 * question per button. Three sets of people use them: those who may offer the gift
 * (gift.can_offer), those who already take part in it, owners included, and its owners,
 * who declare it given and archive it.
 */
#[AsTwigComponent]
final class StatusButtons
{
    public Gift $gift;

    public function __construct(
        private Security $security,
        #[Target('gift_request')]
        private WorkflowInterface $giftRequestWorkflow,
    ) {
    }

    /** Reserve it, or open its pool, from the offer modal. */
    public function canStartOffer(): bool
    {
        return $this->canOffer()
            && ($this->can('reserve') || $this->can('contribute'))
        ;
    }

    public function canJoinPool(): bool
    {
        return $this->canOffer()
            && $this->isPoolOpen()
            && !$this->gift->getPool()->isComplete()
        ;
    }

    public function canEditContribution(): bool
    {
        return $this->takesPart() && $this->isPoolOpen();
    }

    /** Only whoever reserved or chipped in may declare the gift bought. */
    public function canDeclareReady(): bool
    {
        return $this->takesPart() && $this->can('finalize');
    }

    public function canDisengage(): bool
    {
        return $this->takesPart() && $this->can('disengage');
    }

    public function canCancelContribution(): bool
    {
        return $this->takesPart() && $this->can('cancelled_contribution');
    }

    public function canGive(): bool
    {
        return $this->isOwner() && $this->can('give');
    }

    public function canArchive(): bool
    {
        return $this->isOwner() && $this->can('archive');
    }

    private function canOffer(): bool
    {
        return $this->security->isGranted('gift.can_offer', $this->gift);
    }

    private function takesPart(): bool
    {
        $user = $this->security->getUser();

        return $user instanceof User && null !== $this->gift->getParticipant($user);
    }

    private function isOwner(): bool
    {
        return $this->security->isGranted('gift.is_owner', $this->gift);
    }

    private function isPoolOpen(): bool
    {
        return (bool) $this->gift->getStatus()?->isPoolOpen();
    }

    private function can(string $transition): bool
    {
        return $this->giftRequestWorkflow->can($this->gift, $transition);
    }
}
