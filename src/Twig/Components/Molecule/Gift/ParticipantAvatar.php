<?php

namespace App\Twig\Components\Molecule\Gift;

use App\Entity\Participant;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class ParticipantAvatar
{
    public Participant $participant;
    public string $size = '';
}
