<?php

namespace App\Twig\Components\Molecule\Gift;

use App\Entity\Gift;
use App\Enum\GiftStatus;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * Takes either a gift or a bare status, so pages without a persisted Gift
 * (such as the landing page) can show the same badge.
 */
#[AsTwigComponent]
final class StatusBadge
{
    public ?Gift $gift = null;
    public ?GiftStatus $status = null;
    public string $size = 'badge-lg';

    public function getStatus(): ?GiftStatus
    {
        return $this->status ?? $this->gift?->getStatus();
    }

    public function getLabel(): string
    {
        return $this->getStatus()?->label() ?? 'Statut inconnu';
    }

    public function getColorClass(): string
    {
        return $this->getStatus()?->badgeClass() ?? 'badge-neutral';
    }

    public function getIcon(): string
    {
        return $this->getStatus()?->icon() ?? 'lucide:gift';
    }
}
