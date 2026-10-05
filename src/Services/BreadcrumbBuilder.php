<?php

namespace App\Services;

use App\Entity\Crew;
use App\Entity\Gift;
use App\Entity\Section;
use App\Entity\User;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * One method per page and per list: the user's own (/me), a crew's (/crews/{id}) or
 * another user's (/users/{id}). Only the gift's page, shared by all of them, depends on
 * who looks at it.
 */
final class BreadcrumbBuilder
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * The gift's page, reached from the list it is part of for this viewer.
     */
    public function gift(Gift $gift, ?User $viewer): array
    {
        $crew = $gift->getCrew();

        $list = match (true) {
            null !== $crew => [$this->crewItem($crew)],
            $gift->getCreatedBy() === $viewer => [$this->myList()],
            default => [$this->findFriends(), $this->userListItem($gift->getCreatedBy())],
        };

        return [...$list, $this->currentItem($gift->getTitle())];
    }

    public function userList(User $user): array
    {
        return [
            $this->findFriends(),
            $this->currentItem($user->getFullname()),
        ];
    }

    public function newGift(): array
    {
        return [
            $this->myList(),
            $this->currentItem('Nouveau cadeau'),
        ];
    }

    public function editGift(Gift $gift): array
    {
        return [
            $this->myList(),
            $this->item($gift->getTitle(), 'app_gift_show', ['id' => $gift->getId()]),
            $this->currentItem('Modifier'),
        ];
    }

    public function newSection(): array
    {
        return [
            $this->myList(),
            $this->currentItem('Nouvelle section'),
        ];
    }

    public function editSection(Section $section): array
    {
        return [
            $this->myList(),
            $this->currentItem('Modifier « '.$section->getTitle().' »'),
        ];
    }

    public function crew(Crew $crew): array
    {
        return [
            $this->myCrews(),
            $this->currentItem($crew->getName()),
        ];
    }

    public function newCrew(): array
    {
        return [
            $this->myCrews(),
            $this->currentItem('Nouveau groupe'),
        ];
    }

    public function editCrew(Crew $crew): array
    {
        return [
            $this->myCrews(),
            $this->crewItem($crew),
            $this->currentItem('Modifier'),
        ];
    }

    public function newCrewGift(Crew $crew): array
    {
        return [
            $this->crewItem($crew),
            $this->currentItem('Nouveau cadeau'),
        ];
    }

    public function editCrewGift(Gift $gift): array
    {
        return [
            $this->crewItem($gift->getCrew()),
            $this->item($gift->getTitle(), 'app_gift_show', ['id' => $gift->getId()]),
            $this->currentItem('Modifier'),
        ];
    }

    public function newCrewSection(Crew $crew): array
    {
        return [
            $this->crewItem($crew),
            $this->currentItem('Nouvelle section'),
        ];
    }

    public function editCrewSection(Section $section): array
    {
        return [
            $this->crewItem($section->getCrew()),
            $this->currentItem('Modifier « '.$section->getTitle().' »'),
        ];
    }

    private function myList(): array
    {
        return $this->item('Mes sections', 'app_section');
    }

    private function myCrews(): array
    {
        return $this->item('Mes groupes', 'app_crew');
    }

    private function findFriends(): array
    {
        return $this->item('Trouver vos amis', 'app_user');
    }

    private function userListItem(User $user): array
    {
        return $this->item($user->getFullName(), 'app_section_user_page', ['id' => $user->getId()]);
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
