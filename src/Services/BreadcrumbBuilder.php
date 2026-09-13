<?php

namespace App\Services;

use App\Entity\Gift;
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
            $this->item('Mes sections', 'app_section', ['id' => $gift->getCreatedBy()->getId()]),
            $this->currentItem($gift->getTitle()),
        ];
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
