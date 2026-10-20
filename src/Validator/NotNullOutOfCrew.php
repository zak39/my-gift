<?php

namespace App\Validator;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

/**
 * NotNull, out of a crew only: on a gift's or a section's author (createdBy), which a crew's
 * item may outlive (its author's account deleted). The database cannot tell (the crew lives
 * in section.crew_id): the rule lives here, for any form that edits the author.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class NotNullOutOfCrew extends Constraint
{
    public string $message = 'Choisissez qui l\'a créé : seul un cadeau ou une section de groupe peut rester sans auteur.';

    #[HasNamedArguments]
    public function __construct(
        ?string $message = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);

        $this->message = $message ?? $this->message;
    }
}
