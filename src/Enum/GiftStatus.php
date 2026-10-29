<?php

namespace App\Enum;

enum GiftStatus: string
{
    case CREATED = 'created';
    case RESERVED = 'reserved';
    case CONTRIBUTING = 'contributing';
    case READY = 'ready';
    case GIVEN = 'given';
    case ARCHIVED = 'archived';

    public static function status(string $status): self
    {
        return match ($status) {
            self::CREATED->value => self::CREATED,
            self::RESERVED->value => self::RESERVED,
            self::CONTRIBUTING->value => self::CONTRIBUTING,
            self::READY->value => self::READY,
            self::GIVEN->value => self::GIVEN,
            self::ARCHIVED->value => self::ARCHIVED,
            default => throw new \Exception('Unknown status: '.$status),
        };
    }

    /**
     * The pool is still open: a contributor has not declared the gift ready yet, whatever
     * the collected amount. The price is only a target, so reaching it moves nothing.
     */
    public function isPoolOpen(): bool
    {
        return self::CONTRIBUTING === $this;
    }

    /**
     * Someone is giving it, and it is not given yet: reserved, pooled or ready.
     */
    public function isInProgress(): bool
    {
        return match ($this) {
            self::RESERVED, self::CONTRIBUTING, self::READY => true,
            default => false,
        };
    }

    public function hasReachedContributing(): bool
    {
        return match ($this) {
            self::CONTRIBUTING => true,
            self::READY => true,
            self::GIVEN => true,
            self::ARCHIVED => true,
            default => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::CREATED => 'Disponible',
            self::RESERVED => 'Réservé',
            self::CONTRIBUTING => 'Cagnotte en cours',
            self::READY => 'Prêt à offrir',
            self::GIVEN => 'Offert',
            self::ARCHIVED => 'Archivé',
        };
    }

    public function badgeClass(): string
    {
        // Filled badges: `badge-soft` paints the raw colour as text over an 8% tint,
        // which is unreadable for the light hues of the theme.
        return match ($this) {
            self::CREATED => 'badge-neutral',
            self::RESERVED => 'badge-info',
            self::CONTRIBUTING => 'badge-primary',
            self::READY => 'badge-warning',
            self::GIVEN => 'badge-success',
            self::ARCHIVED => 'badge-neutral badge-soft',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::CREATED => 'lucide:gift',
            self::RESERVED => 'lucide:bookmark-check',
            self::CONTRIBUTING => 'lucide:piggy-bank',
            self::READY => 'lucide:package-check',
            self::GIVEN => 'lucide:party-popper',
            self::ARCHIVED => 'lucide:archive',
        };
    }
}
