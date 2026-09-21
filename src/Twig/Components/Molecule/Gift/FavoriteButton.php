<?php

namespace App\Twig\Components\Molecule\Gift;

use App\Entity\Gift;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class FavoriteButton
{
    public Gift $gift;

    public function __construct(private Security $security)
    {
    }

    public function isFavorite(): bool
    {
        $user = $this->security->getUser();

        return $user instanceof User && $user->hasFavoriteGift($this->gift);
    }
}
