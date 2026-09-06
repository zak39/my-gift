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

    public function isReserve(): bool
    {
        return match ($this) {
            self::RESERVED,
            self::READY,
            self::GIVEN,
            self::ARCHIVED => true,
            default => false,
        };
    }

    public function isContributing(): bool
    {
        return match ($this) {
            self::CONTRIBUTING => true,
            default => false,
        };
    }
}
