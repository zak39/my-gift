<?php

namespace App\Services;

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

    public function newGift(): array
    {
        return [
            $this->item('Mes cadeaux', 'app_gift'),
            $this->currentItem('Nouveau cadeau'),
        ];
    }

    public function editGift(Gift $gift): array
    {
        return [
            $this->mySections($gift->getCreatedBy()),
            $this->item($gift->getTitle(), 'app_gift_show', ['id' => $gift->getId()]),
            $this->currentItem('Modifier'),
        ];
    }

    public function newSection(User $user): array
    {
        return [
            $this->mySections($user),
            $this->currentItem('Nouvelle section'),
        ];
    }

    public function editSection(Section $section): array
    {
        return [
            $this->mySections($section->getCreatedBy()),
            $this->currentItem('Modifier « '.$section->getTitle().' »'),
        ];
    }

    private function mySections(User $user): array
    {
        return $this->item('Mes sections', 'app_section', ['id' => $user->getId()]);
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
