<?php

namespace App\Services\Url;

use App\Entity\Crew;
use App\Entity\Gift;
use App\Entity\User;
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
     * no longer see either (an ended friendship must not trap them, see GiftVisibilityVoter),
     * where both would answer an error: they go back to « Mes ami·es » instead, where a
     * giver's way to a gift starts (BreadcrumbBuilder), as for a crew gift whose creator is
     * gone (an account deleted).
     */
    #[AsTwigFunction('gift_viewer_list_url')]
    public function list(Gift $gift): string
    {
        $crew = $this->listCrew($gift);
        $creator = $this->listCreator($gift);

        return match (true) {
            null !== $crew => $this->urlGenerator->generate('app_crew_show', ['id' => $crew->getId()]),
            null !== $creator => $this->urlGenerator->generate('app_section_user_page', ['id' => $creator->getId()]),
            default => $this->urlGenerator->generate('app_friend'),
        };
    }

    /**
     * The crew whose list the viewer goes back to, or null when they may not see it: the
     * one rule for list() and for the gift page's breadcrumb (BreadcrumbBuilder::gift()).
     */
    public function listCrew(Gift $gift): ?Crew
    {
        $crew = $gift->getCrew();

        return null !== $crew && $this->security->isGranted('crew.can_see_list', $crew) ? $crew : null;
    }

    /**
     * The creator whose list the viewer goes back to, or null when they may not see it (a
     * friendship ended) or the creator is gone: the same rule for list() and the breadcrumb.
     */
    public function listCreator(Gift $gift): ?User
    {
        $creator = $gift->getCreatedBy();

        return null !== $creator && $this->security->isGranted('user.can_see_gifts', $creator) ? $creator : null;
    }
}
