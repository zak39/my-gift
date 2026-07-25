<?php

namespace App\Twig\Components;

use App\Repository\UserRepository;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class SearchUsers
{
    use DefaultActionTrait;

    #[LiveProp(writable: true, url: true, onUpdated: 'resetPage')]
    public ?string $query = null;

    #[LiveProp(writable: true, url: true)]
    public int $page = 1;

    public function __construct(
        private UserRepository $userRepository
    )
    {
    }

    /**
     * @return list<\App\Entity\User>
     */
    public function getUsers(): PaginationInterface
    {
        return $this->userRepository->search($this->query, $this->page);
    }

    public function resetPage(): void
    {
        $this->page = 1;
    }

    #[LiveAction]
    public function goToPage(#[LiveArg] int $page): void
    {
        $this->page = $page;
    }
}
