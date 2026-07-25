<?php

namespace App\Twig\Components;

use App\Repository\UserRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class SearchUsers
{
    use DefaultActionTrait;

    #[LiveProp(writable:true, url: true)]
    public ?string $query = null;

    public function __construct(
        private UserRepository $userRepository
    )
    {
    }

    /**
     * @return list<\App\Entity\User>
     */
    public function getUsers(): array
    {
        return $this->userRepository->search($this->query);
    }
}
