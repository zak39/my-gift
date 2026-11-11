<?php

namespace App\Services\Security;

use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/**
 * A new confirmation email for an account not confirmed yet, asked from the login page.
 * The same answer whether the address has an account, a confirmed one or none: nobody
 * learns who is registered. Within the verify_email_resend limiter, per caller's IP
 * (config/packages/rate_limiter.yaml), counted before the lookup for the same reason: one
 * keyed on the address would let anyone use up a victim's allowance.
 */
final class ResendEmailConfirmation
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EmailVerifier $emailVerifier,
        private readonly RateLimiterFactoryInterface $verifyEmailResendLimiter,
    ) {
    }

    /**
     * @param string|null $invitation the slug of the invitation link the account was created
     *                                from, which the new link carries on (EmailVerifier)
     *
     * @return string|null why nothing was sent, or null: sent if the account awaits it
     */
    public function __invoke(string $email, ?string $clientIp, ?string $invitation = null): ?string
    {
        $attempt = $this->verifyEmailResendLimiter->create($clientIp ?? 'unknown')->consume();
        if (!$attempt->isAccepted()) {
            $minutes = max(1, (int) ceil(($attempt->getRetryAfter()->getTimestamp() - time()) / 60));

            return \sprintf('Trop de demandes : réessayez dans %d minute(s).', $minutes);
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (null !== $user && !$user->isVerified()) {
            $this->emailVerifier->sendEmailConfirmation($user, $invitation);
        }

        return null;
    }
}
