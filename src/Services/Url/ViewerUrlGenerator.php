<?php

namespace App\Services\Url;

use App\Entity\Crew;
use App\Entity\Gift;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Attribute\AsTwigFunction;

/**
 * A gift's pages on the side of a user who views it without owning it, a giver: the gift's
 * page at /gift/{id}/show, and the list it is part of. Its owners' pages come from
 * GiftOwnerUrlGenerator.
 */
final class ViewerUrlGenerator
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly Security $security,
    ) {
    }

    #[AsTwigFunction('gift_viewer_show_url')]
    public function show(Gift $gift): string
    {
        return $this->urlGenerator->generate('app_gift_show', ['id' => $gift->getId()]);
    }

    /**
     * The list a gift is part of: its crew's page, or else its creator's. A participant may
     * no longer see a crew's list (an ended friendship must not trap them, see
     * GiftVisibilityVoter): the creator's page then tells them so, where the crew's would
     * answer 403. A crew gift whose creator is gone (an account deleted) leads to their
     * friends' search instead, where a giver's way to a gift starts (BreadcrumbBuilder).
     */
    #[AsTwigFunction('gift_viewer_list_url')]
    public function list(Gift $gift): string
    {
        $crew = $this->listCrew($gift);
        $creator = $gift->getCreatedBy();

        return match (true) {
            null !== $crew => $this->urlGenerator->generate('app_crew_show', ['id' => $crew->getId()]),
            null !== $creator => $this->urlGenerator->generate('app_section_user_page', ['id' => $creator->getId()]),
            default => $this->urlGenerator->generate('app_user'),
        };
    }

    /**
     * The crew whose list the viewer goes back to, or null for the creator's page: the
     * one rule for list() and for the gift page's breadcrumb (BreadcrumbBuilder::gift()).
     */
    public function listCrew(Gift $gift): ?Crew
    {
        $crew = $gift->getCrew();

        return null !== $crew && $this->security->isGranted('crew.can_see_list', $crew) ? $crew : null;
    }
}
