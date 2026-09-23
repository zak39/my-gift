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
     * The pool is still filling up: GiftLifecycle moves a gift to READY as soon as
     * the collected amount reaches the price, so CONTRIBUTING means "not funded yet".
     */
    public function isPoolOpen(): bool
    {
        return self::CONTRIBUTING === $this;
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
}
