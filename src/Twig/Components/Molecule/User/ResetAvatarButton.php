<?php

namespace App\Twig\Components\Molecule\User;

use App\Entity\User;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * Taking the user back to their DiceBear avatar (App\Services\User\ResetAvatar), once
 * confirmed in a modal that says what will be lost.
 */
#[AsTwigComponent]
final class ResetAvatarButton
{
    public User $user;

    /**
     * Something to reset: a photo of their own, or no DiceBear avatar at all. A user on
     * their DiceBear avatar already has nothing to go back to.
     */
    public function isAvailable(): bool
    {
        return null !== $this->user->getAvatar() || null === $this->user->getAvatarDefault();
    }
}
