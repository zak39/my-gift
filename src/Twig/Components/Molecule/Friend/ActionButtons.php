<?php

namespace App\Twig\Components\Molecule\Friend;

use App\Entity\FriendBook;
use App\Entity\FriendRequest;
use App\Entity\User;
use App\Enum\FriendRelation;
use App\Repository\FriendBookRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * The friendship buttons for one person, from the logged-in user's side: ask them as a
 * friend (the caller's `stranger` block), answer a request, cancel one, see a friend's
 * sections, remove, block or unblock.
 *
 * The relationship comes from the logged-in user's book, which the identity map keeps
 * for the whole request: twelve rows in the search load it once.
 */
#[AsTwigComponent]
final class ActionButtons
{
    /** The other person. */
    public User $user;

    /**
     * Classes for a friend's "…" menu, for a caller that places it: Molecule:Friend:Row pins
     * it to the row's corner on a phone, beside the name rather than on a line of its own.
     */
    public string $menuClass = '';

    public function __construct(
        private Security $security,
        private FriendBookRepository $friendBookRepository,
    ) {
    }

    /**
     * A block set by the other person is not a case: they show as a stranger, so
     * nothing reveals the block.
     */
    public function getRelation(): FriendRelation
    {
        return $this->getBook()?->relationWith($this->user) ?? FriendRelation::NONE;
    }

    /**
     * The pending request between the two, whichever way it goes: the buttons post
     * its id to answer or cancel it.
     */
    public function getPendingRequest(): ?FriendRequest
    {
        $book = $this->getBook();

        return $book?->getRequestReceivedFrom($this->user) ?? $book?->getRequestSentTo($this->user);
    }

    private function getBook(): ?FriendBook
    {
        $currentUser = $this->security->getUser();

        return $currentUser instanceof User ? $this->friendBookRepository->of($currentUser) : null;
    }
}
