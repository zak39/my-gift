<?php

namespace App\Services\Url;

use App\Entity\Section;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Attribute\AsTwigFunction;

/**
 * A section's pages, in the list it belongs to: its crew's (/crews/{id}/sections/...) or
 * its author's own (/me/sections/...). Only its owners reach them (section.is_owner).
 */
final class SectionUrlGenerator
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[AsTwigFunction('section_edit_url')]
    public function edit(Section $section): string
    {
        return $this->inItsList($section, 'app_crew_section_edit', 'app_section_edit');
    }

    #[AsTwigFunction('section_delete_url')]
    public function delete(Section $section): string
    {
        return $this->inItsList($section, 'app_crew_section_delete', 'app_section_delete');
    }

    /**
     * The crew's route when the section is a crew's, else its author's own:
     * /crews/{id}/sections/{section}/... or /me/sections/{id}/...
     */
    private function inItsList(Section $section, string $crewRoute, string $ownRoute): string
    {
        $crew = $section->getCrew();

        return null !== $crew
            ? $this->urlGenerator->generate($crewRoute, ['id' => $crew->getId(), 'section' => $section->getId()])
            : $this->urlGenerator->generate($ownRoute, ['id' => $section->getId()])
        ;
    }
}
