<?php

namespace App\Services\Lifecycle;

use App\Entity\FriendRequest;
use App\Entity\User;
use App\Enum\FriendRelation;
use App\Repository\FriendBookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Every change to a relationship between two users goes through here, as GiftLifecycle
 * does for gifts. The workflow guards check who may answer or cancel a request against
 * the logged-in user: for anyone else can() is false, and the method throws.
 */
class FriendRequestLifecycle
{
    public function __construct(
        #[Target('friend_request')]
        private WorkflowInterface $friendRequestWorkflow,
        private EntityManagerInterface $entityManager,
        private FriendBookRepository $friendBookRepository,
    ) {
    }

    public function send(User $sender, User $recipient): void
    {
        $senderBook = $this->friendBookRepository->of($sender);
        $recipientBook = $this->friendBookRepository->of($recipient);

        if ($sender === $recipient) {
            throw new \LogicException('Vous ne pouvez pas vous envoyer une demande à vous-même.');
        }

        if ($recipientBook->hasBlocked($sender)) {
            throw new \LogicException('Vous ne pouvez pas envoyer de demande à cette personne.');
        }

        $error = match ($senderBook->relationWith($recipient)) {
            FriendRelation::FRIEND => 'Vous êtes déjà ami·e avec cette personne.',
            FriendRelation::BLOCKED => 'Vous avez bloqué cette personne. Débloquez-la pour lui envoyer une demande.',
            FriendRelation::REQUEST_SENT => 'Vous avez déjà envoyé une demande à cette personne.',
            FriendRelation::REQUEST_RECEIVED => 'Cette personne vous a déjà envoyé une demande.',
            FriendRelation::NONE => null,
        };

        if (null !== $error) {
            throw new \LogicException($error);
        }

        $friendRequest = (new FriendRequest())
            ->setSender($senderBook)
            ->setRecipient($recipientBook)
        ;

        $this->entityManager->persist($friendRequest);
        $this->entityManager->flush();
    }

    public function accept(FriendRequest $friendRequest): void
    {
        $this->applyTransition($friendRequest, 'accept', 'Cette demande ne peut pas être acceptée.');

        // Both books at once: each user now has the other in getFriends().
        $friendRequest->getSender()->befriend($friendRequest->getRecipient());

        // The friendship lives in FriendBook::$friends from now on: the request has done its job.
        $this->entityManager->remove($friendRequest);
        $this->entityManager->flush();
    }

    public function refuse(FriendRequest $friendRequest): void
    {
        $this->applyTransition($friendRequest, 'refuse', 'Cette demande ne peut pas être refusée.');
        $this->entityManager->remove($friendRequest);
        $this->entityManager->flush();
    }

    public function cancel(FriendRequest $friendRequest): void
    {
        $this->applyTransition($friendRequest, 'cancel', 'Cette demande ne peut pas être annulée.');
        $this->entityManager->remove($friendRequest);
        $this->entityManager->flush();
    }

    public function removeFriend(User $user, User $friend): void
    {
        $book = $this->friendBookRepository->of($user);

        if (!$book->hasFriend($friend)) {
            throw new \LogicException('Cette personne ne fait pas partie de vos ami·es.');
        }

        $book->unfriend($this->friendBookRepository->of($friend));
        $this->entityManager->flush();
    }

    public function block(User $blocker, User $blocked): void
    {
        $book = $this->friendBookRepository->of($blocker);

        if ($blocker === $blocked || $book->hasBlocked($blocked)) {
            throw new \LogicException('Cette personne ne peut pas être bloquée.');
        }

        // Blocking ends everything between the two: the friendship and any pending request.
        $book->unfriend($this->friendBookRepository->of($blocked));

        foreach ([$book->getRequestSentTo($blocked), $book->getRequestReceivedFrom($blocked)] as $friendRequest) {
            if (null !== $friendRequest) {
                $this->entityManager->remove($friendRequest);
            }
        }

        $book->block($blocked);
        $this->entityManager->flush();
    }

    public function unblock(User $blocker, User $blocked): void
    {
        $book = $this->friendBookRepository->of($blocker);

        if (!$book->hasBlocked($blocked)) {
            throw new \LogicException("Cette personne n'est pas bloquée.");
        }

        $book->unblock($blocked);
        $this->entityManager->flush();
    }

    private function applyTransition(FriendRequest $friendRequest, string $transition, string $error): void
    {
        if (!$this->friendRequestWorkflow->can($friendRequest, $transition)) {
            throw new \LogicException($error);
        }

        $this->friendRequestWorkflow->apply($friendRequest, $transition);
    }
}
