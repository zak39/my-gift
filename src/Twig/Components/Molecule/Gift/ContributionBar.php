<?php

namespace App\Twig\Components\Molecule\Gift;

use App\Entity\Gift;
use App\Enum\GiftStatus;
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
     * The pot is for the people chipping in, never for the person being given to:
     * the creator would learn how much has been collected on their own gift.
     * Everyone else keeps seeing it once it is full, hence the collected check:
     * a gift reserved without any contribution has no pot to show.
     */
    public function isVisible(): bool
    {
        if ($this->security->isGranted('gift.is_creator', $this->gift)) {
            return false;
        }

        if (!$this->gift->getPrice()) {
            return false;
        }

        return GiftStatus::CONTRIBUTING === $this->gift->getStatus()
            || $this->getCollected() > 0
        ;
    }

    public function getCollected(): float
    {
        return $this->gift->getTotalContributed();
    }

    /**
     * Clamped for display: a last contributor may give more than what was left,
     * and "il reste -12,00 € à réunir" is not something to put on screen.
     */
    public function getRemaining(): float
    {
        return max(0, $this->gift->getRemainingAmount());
    }

    public function isComplete(): bool
    {
        return $this->gift->getRemainingAmount() <= 0;
    }

    public function getPercent(): int
    {
        $price = $this->gift->getPrice();

        if (!$price) {
            return 0;
        }

        return (int) min(100, round($this->getCollected() / $price * 100));
    }
}
