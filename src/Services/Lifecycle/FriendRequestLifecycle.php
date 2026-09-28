<?php

namespace App\Services\Lifecycle;

use App\Entity\FriendRequest;
use App\Entity\User;
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
    ) {
    }

    public function send(User $sender, User $recipient): void
    {
        $error = match (true) {
            $sender === $recipient => 'Vous ne pouvez pas vous envoyer une demande à vous-même.',
            $sender->hasFriend($recipient) => 'Vous êtes déjà ami·e avec cette personne.',
            $sender->hasBlockedUser($recipient) => 'Vous avez bloqué cette personne. Débloquez-la pour lui envoyer une demande.',
            $recipient->hasBlockedUser($sender) => 'Impossible d\'envoyer une demande à cette personne.',
            null !== $sender->getFriendRequestReceivedFrom($recipient) => 'Cette personne vous a déjà envoyé une demande. Acceptez-la ou refusez-la.',
            null !== $sender->getFriendRequestSentTo($recipient) => 'Vous avez déjà envoyé une demande à cette personne.',
            default => null,
        };

        if (null !== $error) {
            throw new \LogicException($error);
        }

        $friendRequest = (new FriendRequest())
            ->setSender($sender)
            ->setRecipient($recipient)
        ;

        $this->entityManager->persist($friendRequest);
        $this->entityManager->flush();
    }

    public function accept(FriendRequest $friendRequest): void
    {
        $this->applyTransition($friendRequest, 'accept', 'Cette demande ne peut pas être acceptée.');

        // Both sides at once: each user now has the other in getFriends().
        $friendRequest->getSender()->addFriend($friendRequest->getRecipient());

        // The friendship lives in User::$friends from now on: the request has done its job.
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
        if (!$user->hasFriend($friend)) {
            throw new \LogicException('Cette personne ne fait pas partie de vos ami·es.');
        }

        $user->removeFriend($friend);
        $this->entityManager->flush();
    }

    public function block(User $blocker, User $blocked): void
    {
        if ($blocker === $blocked || $blocker->hasBlockedUser($blocked)) {
            throw new \LogicException('Cette personne ne peut pas être bloquée.');
        }

        // Blocking ends everything between the two: the friendship and any pending request.
        $blocker->removeFriend($blocked);

        foreach ([$blocker->getFriendRequestSentTo($blocked), $blocker->getFriendRequestReceivedFrom($blocked)] as $friendRequest) {
            if (null !== $friendRequest) {
                $this->entityManager->remove($friendRequest);
            }
        }

        $blocker->addBlockedUser($blocked);
        $this->entityManager->flush();
    }

    public function unblock(User $blocker, User $blocked): void
    {
        if (!$blocker->hasBlockedUser($blocked)) {
            throw new \LogicException("Cette personne n'est pas bloquée.");
        }

        $blocker->removeBlockedUser($blocked);
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
