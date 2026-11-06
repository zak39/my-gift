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
    /** Label of a centered button to `seeAllRoute`, shown instead of "Voir tout" while empty. */
    public string $emptyActionLabel = '';
}
