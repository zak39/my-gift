<?php

namespace App\Services\Url;

use App\Entity\User;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\UserAuthorizationCheckerInterface;

/**
 * A user's sections (« Voir son profil ») for the reader of an email, or null when the
 * reader may not see them (user.can_see_gifts). Decided when the email is sent, for the
 * reader rather than whoever is logged in: the worker renders it later, from users it
 * deserialized. Each reader gets an email of their own, then: a link one recipient may
 * follow is a 404 to another.
 */
final class ProfileUrlGenerator
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly UserAuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    /**
     * @param ?User $user null for an email that shows no card
     */
    public function forReader(User $reader, ?User $user): ?string
    {
        if (null === $user || !$this->authorizationChecker->isGrantedForUser($reader, 'user.can_see_gifts', $user)) {
            return null;
        }

        return $this->urlGenerator->generate('app_section_user_page', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
