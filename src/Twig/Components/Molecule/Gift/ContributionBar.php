<?php

namespace App\Twig\Components\Molecule\Gift;

use App\Entity\Gift;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class ContributionBar
{
    public Gift $gift;

    public function __construct(private Security $security)
    {
    }

    /**
     * A gift reserved without any contribution has no pool to show; who may see
     * the amounts is decided by GiftPoolVoter.
     */
    public function isVisible(): bool
    {
        if (!$this->security->isGranted('gift.can_see_amounts', $this->gift)) {
            return false;
        }

        return $this->gift->getPool()->isPooled();
    }

    public function getProgressColor(): string
    {
        return match (true) {
            $this->gift->getPool()->getExceededAmount() > 0 => 'progress-warning',
            $this->gift->getPool()->isComplete() => 'progress-success',
            default => 'progress-primary',
        };
    }
}
