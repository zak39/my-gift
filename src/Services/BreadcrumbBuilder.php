<?php

namespace App\Services;

use App\Entity\Crew;
use App\Entity\Gift;
use App\Entity\Section;
use App\Entity\User;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class BreadcrumbBuilder
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function giftFromUserPage(Gift $gift): array
    {
        $user = $gift->getCreatedBy();

        return [
            $this->item('Trouver vos amis', 'app_user'),
            $this->item($user->getFullName(), 'app_section_user_page', ['id' => $user->getId()]),
            $this->currentItem($gift->getTitle()),
        ];
    }

    public function giftFromUserSection(User $user): array
    {
        return [
            $this->item('Trouver vos amis', 'app_user'),
            $this->currentItem($user->getFullname()),
        ];
    }

    public function giftFromMySections(Gift $gift): array
    {
        return [
            $this->mySections($gift->getCreatedBy()),
            $this->currentItem($gift->getTitle()),
        ];
    }

    /**
     * The same for every viewer, member or friend: the crew's page is the list for both.
     */
    public function giftFromCrew(Gift $gift): array
    {
        return [
            $this->crewItem($gift->getCrew()),
            $this->currentItem($gift->getTitle()),
        ];
    }

    /**
     * A gift added from a section of a crew's list goes straight into it.
     */
    public function newGift(Gift $gift): array
    {
        $crew = $gift->getCrew();

        return [
            null !== $crew ? $this->crewItem($crew) : $this->item('Mes cadeaux', 'app_gift'),
            $this->currentItem('Nouveau cadeau'),
        ];
    }

    public function editGift(Gift $gift): array
    {
        return [
            $this->listOf($gift->getCrew(), $gift->getCreatedBy()),
            $this->item($gift->getTitle(), 'app_gift_show', ['id' => $gift->getId()]),
            $this->currentItem('Modifier'),
        ];
    }

    public function newSection(Section $section): array
    {
        return [
            $this->listOf($section->getCrew(), $section->getCreatedBy()),
            $this->currentItem('Nouvelle section'),
        ];
    }

    public function editSection(Section $section): array
    {
        return [
            $this->listOf($section->getCrew(), $section->getCreatedBy()),
            $this->currentItem('Modifier « '.$section->getTitle().' »'),
        ];
    }

    public function crew(Crew $crew): array
    {
        return [
            $this->item('Mes groupes', 'app_crew'),
            $this->currentItem($crew->getName()),
        ];
    }

    public function newCrew(): array
    {
        return [
            $this->item('Mes groupes', 'app_crew'),
            $this->currentItem('Nouveau groupe'),
        ];
    }

    public function editCrew(Crew $crew): array
    {
        return [
            $this->item('Mes groupes', 'app_crew'),
            $this->crewItem($crew),
            $this->currentItem('Modifier'),
        ];
    }

    private function mySections(User $user): array
    {
        return $this->item('Mes sections', 'app_section', ['id' => $user->getId()]);
    }

    /**
     * The list a gift or a section belongs to: its crew's, or else its creator's own.
     * Never the creator's for a crew, whose page the other members may not open.
     */
    private function listOf(?Crew $crew, User $creator): array
    {
        return null !== $crew ? $this->crewItem($crew) : $this->mySections($creator);
    }

    private function crewItem(Crew $crew): array
    {
        return $this->item($crew->getName(), 'app_crew_show', ['id' => $crew->getId()]);
    }

    private function item(string $label, string $route, array $params = []): array
    {
        return ['label' => $label, 'url' => $this->urlGenerator->generate($route, $params)];
    }

    private function currentItem(string $label): array
    {
        return ['label' => $label, 'url' => null];
    }
}
