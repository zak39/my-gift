<?php

namespace App\Twig\Components\Molecule\Dashboard;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class GiftTabPanel
{
    /** @var \App\Entity\Gift[] */
    public array $gifts = [];
    public string $emptyMessage = 'Aucun cadeau à afficher.';
    public string $seeAllRoute = '';
}
