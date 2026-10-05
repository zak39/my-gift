<?php

namespace App\ValueObject;

use App\Entity\Participant;
use App\Enum\GiftStatus;

/**
 * The money friends put together towards a gift, read from its price and participants.
 * Not persisted: Gift::getPool() builds it on demand, so it always reflects the gift.
 *
 * Every gift has one, empty for a gift nobody chipped in for: a pool is a state of the
 * gift that changes over its life, not a kind of gift.
 */
final class GiftPool
{
    /**
     * @param iterable<Participant> $participants
     */
    public function __construct(
        private readonly ?float $price,
        private readonly iterable $participants,
        private readonly ?GiftStatus $status,
    ) {
    }

    public function getTotalContributed(): float
    {
        $total = 0;
        foreach ($this->participants as $participant) {
            $total += $participant->getAmount() ?? 0;
        }

        return $total;
    }

    public function getRemainingAmount(): float
    {
        return $this->getRemainingAmountExcluding();
    }

    /**
     * Clamped for display: a price lowered below what is already collected makes
     * getRemainingAmount() negative, which getExceededAmount() reports instead.
     */
    public function getRemainingToCollect(): float
    {
        return max(0, $this->getRemainingAmount());
    }

    /**
     * The price is a target the creator may lower after people chipped in: what the
     * pool collected beyond it, for the contributors to settle between themselves.
     */
    public function getExceededAmount(): float
    {
        return max(0, -$this->getRemainingAmount());
    }

    /**
     * Several people chipping in, as opposed to a single reservation with no money.
     */
    public function isPooled(): bool
    {
        return $this->getTotalContributed() > 0
            || $this->status?->isPoolOpen()
        ;
    }

    public function isComplete(): bool
    {
        return $this->price > 0 && $this->getRemainingAmount() <= 0;
    }

    public function getContributionPercent(): int
    {
        if (!$this->price) {
            return 0;
        }

        return (int) min(100, round($this->getTotalContributed() / $this->price * 100));
    }

    /**
     * Rounded to the cent: amounts are floats, so 60.30 - (20.10 + 40.20) gives
     * -7.1e-15 instead of 0, which reads as a pool that exceeds its target.
     *
     * The excluded participant is skipped rather than subtracted: a new one being
     * validated is not in the collection yet.
     */
    public function getRemainingAmountExcluding(?Participant $excluded = null): float
    {
        $total = 0;
        foreach ($this->participants as $participant) {
            if ($excluded === $participant) {
                continue;
            }

            $total += $participant->getAmount();
        }

        return round($this->price - $total, 2);
    }
}
