<?php

namespace App\Twig\Components\Organism;

use App\Entity\User;
use App\Repository\UserRepository;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The search of the « Ami·es » tab: the logged-in user's friends only, by pseudo or full
 * name, paged. Nobody else is ever listed (UserRepository::searchFriends()): people come in
 * through an invitation link, not by browsing the users.
 *
 * Its re-renders come through /_components, outside /me's access control: a live component
 * is a controller, so #[IsGranted] on the class guards them and its actions.
 */
#[AsLiveComponent]
#[IsGranted('IS_AUTHENTICATED')]
final class FriendSearch
{
    use DefaultActionTrait;

    #[LiveProp(writable: true, url: true, onUpdated: 'resetPage')]
    public ?string $query = null;

    #[LiveProp(writable: true, url: true)]
    public int $page = 1;

    public function __construct(
        private UserRepository $userRepository,
        private Security $security,
    ) {
    }

    /**
     * @return PaginationInterface<int, User>
     */
    public function getFriends(): PaginationInterface
    {
        /** @var User $user */
        $user = $this->security->getUser();

        return $this->userRepository->searchFriends($user, $this->query, $this->page);
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
