<?php

namespace App\Services\Friend;

use App\Entity\User;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/**
 * Counts a slug the user tries for their invitation link, within the invitation_slug_change
 * limiter (config/packages/rate_limiter.yaml). Before the form checks it: its « déjà pris »
 * says which slugs are in use, so trying words there must stay as slow as opening links
 * (FindInvitation).
 */
final class CheckInvitationSlugAttempt
{
    public function __construct(
        private readonly RateLimiterFactoryInterface $invitationSlugChangeLimiter,
    ) {
    }

    /**
     * @return string|null why the attempt is refused, or null when it may be checked
     */
    public function __invoke(User $user): ?string
    {
        $attempt = $this->invitationSlugChangeLimiter->create((string) $user->getId())->consume();
        if ($attempt->isAccepted()) {
            return null;
        }

        $minutes = max(1, (int) ceil(($attempt->getRetryAfter()->getTimestamp() - time()) / 60));

        return \sprintf('Trop de tentatives : réessayez dans %d minute(s).', $minutes);
    }
}
