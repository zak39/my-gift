<?php

namespace App\Services\Security;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/**
 * Checks the password a form asks again (changing it, deleting the account), within the
 * password_check limiter shared by those forms (config/packages/rate_limiter.yaml). The
 * attempt is counted before the check: a wrong guess and a right one cost the same.
 */
final class CheckCurrentPassword
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly RateLimiterFactoryInterface $passwordCheckLimiter,
    ) {
    }

    /**
     * @return string|null why the password is refused, or null when it is the right one
     */
    public function __invoke(User $user, string $password): ?string
    {
        $attempt = $this->passwordCheckLimiter->create((string) $user->getId())->consume();
        if (!$attempt->isAccepted()) {
            $minutes = max(1, (int) ceil(($attempt->getRetryAfter()->getTimestamp() - time()) / 60));

            return \sprintf('Trop de tentatives : réessayez dans %d minute(s).', $minutes);
        }

        if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            return 'Mot de passe incorrect.';
        }

        return null;
    }
}
