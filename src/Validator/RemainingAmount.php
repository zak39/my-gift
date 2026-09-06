<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class RemainingAmount extends Constraint
{
    public ?string $message = 'Le montant de {{ amount }}€ dépasse le reste à payer de {{ remaining }}€.';
    public string $mode = 'strict';

    public function __construct(
        ?string $mode = null,
        ?string $message = null,
        ?array $groups = null,
        $payload = null,
    ) {
        $this->mode = $mode ?? $this->mode;
        $this->message = $message ?? $this->message;

        return parent::__construct(null, $groups, $payload);
    }

    // in the base Symfony\Component\Validator\Constraint class
    public function validatedBy(): string
    {
        return static::class.'Validator';
    }
}
