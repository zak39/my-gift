<?php

namespace App\Services\Url;

use App\Entity\Crew;
use App\Entity\Gift;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\HttpFoundation\UrlHelper;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Attribute\AsTwigFunction;

/**
 * The links the "Partager" buttons copy: a gift's page, a user's list, a crew's, as their
 * friends open them. Signed (`_hash`, `_expiration`) for whoever may see what the link
 * shows, so that its card unfolds in a messenger: a link-preview bot is never logged in,
 * and only a signed link shows it more than the app's own card (SharedLinkPreviewListener).
 * Anyone else gets the bare link: guessing ids, or sharing a list one cannot see, unfolds
 * nothing.
 *
 * The signature only ever unfolds the card: a friend logged in opens the page with or
 * without it, even once it has expired. The paths are the ones linked from emails, which
 * stay unsigned.
 */
final class ShareUrlGenerator
{
    /**
     * Long enough for a list shared well before the occasion, short enough that a link
     * leaked or kept after a friendship ended stops showing its card.
     */
    private const VALIDITY = 'P6M';

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly ViewerUrlGenerator $viewerUrlGenerator,
        private readonly UrlHelper $urlHelper,
        private readonly UriSigner $uriSigner,
        private readonly Security $security,
    ) {
    }

    #[AsTwigFunction('gift_share_url')]
    public function gift(Gift $gift): string
    {
        return $this->share($this->urlHelper->getAbsoluteUrl($this->viewerUrlGenerator->show($gift)), 'gift.can_see', $gift);
    }

    #[AsTwigFunction('user_list_share_url')]
    public function userList(User $user): string
    {
        return $this->share($this->absoluteUrl('app_section_user_page', $user->getId()), 'user.can_see_gifts', $user);
    }

    #[AsTwigFunction('crew_share_url')]
    public function crew(Crew $crew): string
    {
        return $this->share($this->absoluteUrl('app_crew_show', $crew->getId()), 'crew.can_see_list', $crew);
    }

    /**
     * @param string $canSee the voter attribute telling who may see what the link shows
     */
    private function share(string $url, string $canSee, Gift|User|Crew $subject): string
    {
        return $this->security->isGranted($canSee, $subject)
            ? $this->uriSigner->sign($url, new \DateInterval(self::VALIDITY))
            : $url
        ;
    }

    private function absoluteUrl(string $route, int $id): string
    {
        return $this->urlGenerator->generate($route, ['id' => $id], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
