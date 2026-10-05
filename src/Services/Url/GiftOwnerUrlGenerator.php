<?php

namespace App\Services\Url;

use App\Entity\Gift;
use App\Entity\Section;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Attribute\AsTwigFunction;

/**
 * A gift's pages on its owners' side: in its crew's list (/crews/{id}/...) when it is filed
 * in a crew's section, else in its author's own (/me/...). The caller already knows the user
 * owns the gift (a Me/ or Crew/ controller, a gift.is_owner check); a giver's pages come
 * from ViewerUrlGenerator.
 */
final class GiftOwnerUrlGenerator
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * The list the gift is filed in, where an owner lands after changing it: an edit may
     * have moved it between their own list and a crew's.
     */
    public function list(Gift $gift): string
    {
        $crew = $gift->getCrew();

        return null !== $crew
            ? $this->urlGenerator->generate('app_crew_show', ['id' => $crew->getId()])
            : $this->urlGenerator->generate('app_section')
        ;
    }

    #[AsTwigFunction('gift_owner_show_url')]
    public function show(Gift $gift): string
    {
        return $this->inItsList($gift, 'app_crew_gift_show', 'app_me_gift_show');
    }

    #[AsTwigFunction('gift_create_url')]
    public function create(Section $section): string
    {
        $crew = $section->getCrew();

        return null !== $crew
            ? $this->urlGenerator->generate('app_crew_gift_create', ['id' => $crew->getId(), 'section' => $section->getId()])
            : $this->urlGenerator->generate('app_gift_create', ['section' => $section->getId()])
        ;
    }

    #[AsTwigFunction('gift_edit_url')]
    public function edit(Gift $gift): string
    {
        return $this->inItsList($gift, 'app_crew_gift_edit', 'app_gift_edit');
    }

    #[AsTwigFunction('gift_delete_url')]
    public function delete(Gift $gift): string
    {
        return $this->inItsList($gift, 'app_crew_gift_delete', 'app_gift_delete');
    }

    /**
     * The crew's route when the gift is filed in a crew's section, else its author's own:
     * the two lists name a gift's pages alike, /crews/{id}/gifts/{gift}/... and /me/gifts/{id}/...
     */
    private function inItsList(Gift $gift, string $crewRoute, string $ownRoute): string
    {
        $crew = $gift->getCrew();

        return null !== $crew
            ? $this->urlGenerator->generate($crewRoute, ['id' => $crew->getId(), 'gift' => $gift->getId()])
            : $this->urlGenerator->generate($ownRoute, ['id' => $gift->getId()])
        ;
    }
}
