<?php

namespace App\Twig\Components\Molecule;

use App\Entity\User;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class UserCard
{
    public User $user;

    /**
     * Required through mount(): the template reads the user throughout, so a missing one
     * would only fail further down, in path(), with a 500, instead of a clear error here.
     */
    public function mount(User $user): void
    {
        $this->user = $user;
    }
}
