<?php

namespace App\Services\User;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Saves a user whose form was valid, unless another took the same pseudo meanwhile.
 *
 * UniqueEntity checked it before, but two saves of "Jean" and "jean" at the same moment
 * both pass it, each before the other is saved, and the database's index tells cases
 * apart. Under a lock on the pseudo (UserRepository::lockPseudo()) the second one waits,
 * then UniqueEntity runs again, sees the first, and its error goes on $pseudoField as the
 * form's own would, suggestions included (Molecule:Form:PseudoSuggestion reads its code).
 */
final class SaveUserPseudo
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * @return bool false when the pseudo was taken meanwhile: the error is on $pseudoField
     */
    public function __invoke(User $user, FormInterface $pseudoField): bool
    {
        // Not wrapInTransaction(): it flushes after its callback, which would save the user,
        // changed by the form, with the pseudo found taken.
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $this->userRepository->lockPseudo((string) $user->getPseudo());
            $taken = $this->pseudoTaken($user);

            if (null === $taken) {
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            }

            // Committed either way: a lock-only transaction releases its lock.
            $connection->commit();
        } catch (\Throwable $exception) {
            $connection->rollBack();

            throw $exception;
        }

        if (null !== $taken) {
            $pseudoField->addError(new FormError($taken->getMessage(), $taken->getMessageTemplate(), $taken->getParameters(), $taken->getPlural(), $taken));
        }

        return null === $taken;
    }

    /**
     * UniqueEntity's violation on the pseudo, now that the other save, if any, is visible.
     */
    private function pseudoTaken(User $user): ?ConstraintViolationInterface
    {
        foreach ($this->validator->validate($user) as $violation) {
            if ('pseudo' === $violation->getPropertyPath() && UniqueEntity::NOT_UNIQUE_ERROR === $violation->getCode()) {
                return $violation;
            }
        }

        return null;
    }
}
