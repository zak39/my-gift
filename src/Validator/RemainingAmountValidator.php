<?php

namespace App\Validator;

use App\Entity\Participant;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class RemainingAmountValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint)
    {
        if (!$constraint instanceof RemainingAmount) {
            throw new UnexpectedTypeException($constraint, RemainingAmount::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        $participant = $this->context->getObject();

        if (!$participant instanceof Participant) {
            return;
        }

        $gift = $participant->getGift();

        if (null === $gift) {
            return;
        }

        /*
        $otherParticipantsTotal = 0;
        foreach ($gift->getParticipants() as $otherParticipant) {
            if ($otherParticipant === $participant) {
                continue;
            }

            $otherParticipantsTotal += $otherParticipant->getAmount() ?? 0;
        }

        $remaining = $gift->getPrice() - $otherParticipantsTotal;
        */
        $remaining = $gift->getRemainingAmountExcluding($participant);

        if ($value > $remaining) {
            $this->context
                ->buildViolation($constraint->message)
                ->setParameter('{{ remaining }}', (string) $remaining)
                ->setParameter('{{ amount }}', (string) $value)
                ->addViolation()
            ;
        }
    }
}
