<?php

namespace App\Twig\Components\Organism;

use App\Entity\Gift;
use App\Enum\GiftStatus;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Contributors
{
    public Gift $gift;

    public function __construct(private Security $security)
    {
    }

    public function isCreator(): bool
    {
        return $this->security->isGranted('gift.is_creator', $this->gift);
    }

    /**
     * `contributing` means the collected amount has not reached the price yet.
     */
    public function isPotInProgress(): bool
    {
        return GiftStatus::CONTRIBUTING === $this->gift->getStatus();
    }

    /**
     * Several people chipping in, as opposed to a single reservation with no money.
     */
    public function isPooled(): bool
    {
        return $this->gift->getTotalContributed() > 0 || $this->isPotInProgress();
    }

    /**
     * The creator may see who is giving, except while a pot is still filling up.
     */
    public function showWho(): bool
    {
        if ($this->gift->getParticipants()->isEmpty()) {
            return false;
        }

        return !($this->isCreator() && $this->isPotInProgress());
    }

    /**
     * Amounts are for the people chipping in, never for the person being given to.
     */
    public function showAmounts(): bool
    {
        return !$this->isCreator();
    }

    public function showPot(): bool
    {
        return $this->showAmounts() && $this->gift->getPrice() > 0;
    }

    public function getPercent(): int
    {
        $price = $this->gift->getPrice();

        if (!$price) {
            return 0;
        }

        return (int) min(100, round($this->gift->getTotalContributed() / $price * 100));
    }

    /**
     * Clamped for display: a last contributor may give more than what was left.
     */
    public function getRemaining(): float
    {
        return max(0, $this->gift->getRemainingAmount());
    }
}
