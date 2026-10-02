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

    public function isPooled(): bool
    {
        return $this->gift->isPooled();
    }

    /**
     * The owners are told a pool exists without learning anything about it.
     */
    public function isWaitingOnPool(): bool
    {
        return $this->gift->getStatus()?->isPoolOpen()
            && !$this->security->isGranted('gift.can_see_contributors', $this->gift)
        ;
    }

    public function isOwner(): bool
    {
        return $this->security->isGranted('gift.is_owner', $this->gift);
    }
}
