<?php

namespace App\Twig\Components\Organism;

use App\Entity\Gift;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Contributors
{
    public Gift $gift;

    public function __construct(private Security $security)
    {
    }

    public function showWho(): bool
    {
        return !$this->gift->getParticipants()->isEmpty()
            && $this->security->isGranted('gift.can_see_contributors', $this->gift)
        ;
    }

    public function showAmounts(): bool
    {
        return $this->security->isGranted('gift.can_see_amounts', $this->gift);
    }

    public function showPot(): bool
    {
        return $this->gift->getPrice() > 0 && $this->showAmounts();
    }

    /**
     * Several people chipping in, as opposed to a single reservation with no money.
     */
    public function isPooled(): bool
    {
        return $this->gift->getTotalContributed() > 0
            || $this->gift->getStatus()?->isPoolOpen()
        ;
    }

    /**
     * The creator is told a pool exists without learning anything about it.
     */
    public function isWaitingOnPool(): bool
    {
        return $this->gift->getStatus()?->isPoolOpen()
            && !$this->security->isGranted('gift.can_see_contributors', $this->gift)
        ;
    }

    public function isCreator(): bool
    {
        return $this->security->isGranted('gift.is_creator', $this->gift);
    }
}
