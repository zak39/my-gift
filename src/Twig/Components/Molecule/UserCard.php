<?php

namespace App\Twig\Components\Molecule;

use App\Entity\User;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class UserCard
{
    public ?User $user = null;
}
