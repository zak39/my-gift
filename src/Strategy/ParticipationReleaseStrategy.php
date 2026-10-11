<?php

namespace App\Strategy;

use App\Entity\Participant;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * What becomes of a participation when its user deletes their account. One rule per case,
 * each in its own class under src/Services/Participation/: add or change a rule there.
 * ReleaseParticipation picks the case that applies.
 *
 * The cases exclude each other: for any participation, exactly one supports() it.
 */
#[AutoconfigureTag]
interface ParticipationReleaseStrategy
{
    public function supports(Participant $participation): bool;

    public function release(Participant $participation): void;
}
