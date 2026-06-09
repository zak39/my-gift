<?php

namespace App\Enum;

enum GiftStatus: string
{
    case CREATED = 'created';
    case RESERVED = 'reserved';
    case CONTRIBUTING = 'contributing';
    case READY = 'ready';
    case GIVEN = 'given';
    case DELETED = 'deleted';

    public static function status(string $status): self
    {
        return match ($status) {
            self::CREATED->value => self::CREATED,
            self::RESERVED->value => self::RESERVED,
            self::CONTRIBUTING->value => self::CONTRIBUTING,
            self::READY->value => self::READY,
            self::GIVEN->value => self::GIVEN,
            self::DELETED->value => self::DELETED,
            default => throw new \Exception('Unknown status: '.$status),
        };
    }
}
