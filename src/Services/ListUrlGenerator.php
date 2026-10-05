<?php

namespace App\Services;

use App\Entity\Gift;
use App\Entity\Section;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Attribute\AsTwigFunction;

/**
 * Where a gift or a section lives, as URLs: a crew's list (/crews/{id}/...) or the
 * user's own (/me/...). The one place that tells them apart, for the controllers'
 * redirects and the templates' links alike.
 */
final class ListUrlGenerator
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly Security $security,
    ) {
    }

    /**
     * The list a gift is part of, as the current user reaches it: its crew's page, their
     * own list for their gift, or else its creator's page. A participant may no longer see
     * a crew's list (an ended friendship must not trap them, see GiftVisibilityVoter): the
     * creator's page then tells them so, where the crew's would answer 403.
     */
    #[AsTwigFunction('gift_list_url')]
    public function giftList(Gift $gift): string
    {
        $crew = $gift->getCrew();

        if (null !== $crew && $this->security->isGranted('crew.can_see_list', $crew)) {
            return $this->urlGenerator->generate('app_crew_show', ['id' => $crew->getId()]);
        }

        if (null === $crew && $gift->getCreatedBy() === $this->security->getUser()) {
            return $this->urlGenerator->generate('app_section');
        }

        return $this->urlGenerator->generate('app_section_user_page', ['id' => $gift->getCreatedBy()->getId()]);
    }

    #[AsTwigFunction('gift_create_url')]
    public function giftCreate(Section $section): string
    {
        $crew = $section->getCrew();

        return null !== $crew
            ? $this->urlGenerator->generate('app_crew_gift_create', ['id' => $crew->getId(), 'section' => $section->getId()])
            : $this->urlGenerator->generate('app_gift_create', ['section' => $section->getId()])
        ;
    }

    #[AsTwigFunction('gift_edit_url')]
    public function giftEdit(Gift $gift): string
    {
        $crew = $gift->getCrew();

        return null !== $crew
            ? $this->urlGenerator->generate('app_crew_gift_edit', ['id' => $crew->getId(), 'gift' => $gift->getId()])
            : $this->urlGenerator->generate('app_gift_edit', ['id' => $gift->getId()])
        ;
    }

    #[AsTwigFunction('gift_delete_url')]
    public function giftDelete(Gift $gift): string
    {
        $crew = $gift->getCrew();

        return null !== $crew
            ? $this->urlGenerator->generate('app_crew_gift_delete', ['id' => $crew->getId(), 'gift' => $gift->getId()])
            : $this->urlGenerator->generate('app_gift_delete', ['id' => $gift->getId()])
        ;
    }

    #[AsTwigFunction('section_edit_url')]
    public function sectionEdit(Section $section): string
    {
        $crew = $section->getCrew();

        return null !== $crew
            ? $this->urlGenerator->generate('app_crew_section_edit', ['id' => $crew->getId(), 'section' => $section->getId()])
            : $this->urlGenerator->generate('app_section_edit', ['id' => $section->getId()])
        ;
    }

    #[AsTwigFunction('section_delete_url')]
    public function sectionDelete(Section $section): string
    {
        $crew = $section->getCrew();

        return null !== $crew
            ? $this->urlGenerator->generate('app_crew_section_delete', ['id' => $crew->getId(), 'section' => $section->getId()])
            : $this->urlGenerator->generate('app_section_delete', ['id' => $section->getId()])
        ;
    }
}
