<?php

namespace App\Services;

use App\Entity\User;
use App\Services\Security\CheckCurrentPassword;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final class UserProfileService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly CheckCurrentPassword $checkCurrentPassword,
    ) {
    }

    public function processSubmission(FormInterface $form, User $user, Request $request): ProfileUpdateResult
    {
        $this->validatePasswordChange($form, $user);

        if (!$form->isValid()) {
            return new ProfileUpdateResult(ProfileUpdateResult::INVALID, ProfileUpdateResult::DO_NOT_USE_TURBO, $user);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $updatedUser = $this->refreshAuthenticatedUser($user);

        return new ProfileUpdateResult(
            ProfileUpdateResult::VALID,
            $this->isTurboRequest($request) ? ProfileUpdateResult::USE_TURBO : ProfileUpdateResult::DO_NOT_USE_TURBO,
            $updatedUser
        );
    }

    private function validatePasswordChange(FormInterface $form, User $user): void
    {
        $plainPassword = $form->get('plainPassword')->getData();
        if (!$plainPassword) {
            return;
        }

        $oldPassword = $form->get('oldPassword')->getData();
        if (!$oldPassword) {
            $form->get('oldPassword')->addError(new FormError('Le mot de passe actuel est requis pour changer le mot de passe.'));
            $form->addError(new FormError('Vous devez saisir votre mot de passe actuel pour changer le mot de passe.'));

            return;
        }

        $refusal = ($this->checkCurrentPassword)($user, $oldPassword);
        if (null !== $refusal) {
            $form->get('oldPassword')->addError(new FormError($refusal));
            $form->addError(new FormError('Votre mot de passe n\'a pas été modifié.'));

            return;
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
    }

    private function refreshAuthenticatedUser(User $user): User
    {
        $refreshedUser = $this->entityManager->find(User::class, $user->getId());
        if ($refreshedUser) {
            $this->tokenStorage->setToken(new UsernamePasswordToken(
                $refreshedUser,
                'main',
                $refreshedUser->getRoles()
            ));

            return $refreshedUser;
        }

        return $user;
    }

    private function isTurboRequest(Request $request): bool
    {
        return $request->headers->get('Turbo-Frame') || $request->headers->get('X-Turbo-Frame');
    }
}
