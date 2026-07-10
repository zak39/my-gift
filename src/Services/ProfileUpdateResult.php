<?php

namespace App\Services;

use App\Entity\User;

final class ProfileUpdateResult
{
    public const INVALID = false;
    public const VALID = true;
    public const USE_TURBO = true;
    public const DO_NOT_USE_TURBO = false;

    public function __construct(
        private readonly bool $isValid,
        private readonly bool $shouldUseTurbo,
        private readonly ?User $user = null,
    ) {
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function shouldUseTurbo(): bool
    {
        return $this->shouldUseTurbo;
    }

    public function user(): ?User
    {
        return $this->user;
    }
}
