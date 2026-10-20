<?php

namespace App\Validator;

use App\Entity\Gift;
use App\Entity\Section;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class NotNullOutOfCrewValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof NotNullOutOfCrew) {
            throw new UnexpectedTypeException($constraint, NotNullOutOfCrew::class);
        }

        // Unlike most constraints, null is the case to judge here, not one to leave to NotNull.
        if (null !== $value) {
            return;
        }

        $item = $this->context->getObject();

        if (!$item instanceof Gift && !$item instanceof Section) {
            throw new UnexpectedValueException($item, Gift::class.'|'.Section::class);
        }

        if (null !== $item->getCrew()) {
            return;
        }

        $this->context
            ->buildViolation($constraint->message)
            ->addViolation()
        ;
    }
}
