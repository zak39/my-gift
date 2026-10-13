<?php

namespace App\Enum;

enum ParticipantStatus: string
{
    case RESERVED = 'reserved';
    case PARTICIPATING = 'participating';
    case CANCELLED = 'cancelled';

    public static function status(string $status): self
    {
        return match ($status) {
            self::RESERVED->value => self::RESERVED,
            self::PARTICIPATING->value => self::PARTICIPATING,
            self::CANCELLED->value => self::CANCELLED,
            default => throw new \Exception('Unknown status: '.$status),
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::RESERVED => 'Réservation',
            self::PARTICIPATING => 'Contribution',
            self::CANCELLED => 'Annulée',
        };
    }
}
