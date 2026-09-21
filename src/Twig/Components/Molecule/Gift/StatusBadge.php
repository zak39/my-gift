<?php

namespace App\Twig\Components\Molecule\Gift;

use App\Entity\Gift;
use App\Enum\GiftStatus;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class StatusBadge
{
    public ?Gift $gift = null;

    public function getLabel(): string
    {
        return match ($this->gift?->getStatus()) {
            GiftStatus::CREATED => 'Disponible',
            GiftStatus::RESERVED => 'Réservé',
            GiftStatus::CONTRIBUTING => 'Cagnotte en cours',
            GiftStatus::READY => 'Prêt à offrir',
            GiftStatus::GIVEN => 'Offert',
            GiftStatus::ARCHIVED => 'Archivé',
            default => 'Statut inconnu',
        };
    }

    public function getColorClass(): string
    {
        // Filled badges: `badge-soft` paints the raw colour as text over an 8% tint,
        // which is unreadable for the light hues of the theme.
        return match ($this->gift?->getStatus()) {
            GiftStatus::RESERVED => 'badge-info',
            GiftStatus::CONTRIBUTING => 'badge-accent',
            GiftStatus::READY => 'badge-warning',
            GiftStatus::GIVEN => 'badge-success',
            GiftStatus::ARCHIVED => 'badge-neutral badge-soft',
            default => 'badge-neutral',
        };
    }

    public function getIcon(): string
    {
        return match ($this->gift?->getStatus()) {
            GiftStatus::RESERVED => 'lucide:bookmark-check',
            GiftStatus::CONTRIBUTING => 'lucide:piggy-bank',
            GiftStatus::READY => 'lucide:package-check',
            GiftStatus::GIVEN => 'lucide:party-popper',
            GiftStatus::ARCHIVED => 'lucide:archive',
            default => 'lucide:gift',
        };
    }
}
