<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Repository\FriendBookRepository;
use App\Services\Lifecycle\FriendRequestLifecycle;
use App\Services\RefererRedirector;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * What the user does to another one: ask them as a friend, unfriend, block. Answering a
 * request is Me\FriendController's.
 */
#[IsGranted('IS_AUTHENTICATED')]
#[Route('/users/{id}', requirements: ['id' => '\d+'])]
final class FriendshipController extends AbstractController
{
    #[Route('/friend/{action}', name: 'app_friend_action', requirements: ['action' => 'send|remove|block|unblock'], methods: ['POST'])]
    public function act(User $other, string $action, Request $request, FriendRequestLifecycle $friendRequestLifecycle, FriendBookRepository $friendBookRepository, RefererRedirector $refererRedirector): Response
    {
        if (!$this->isCsrfTokenValid("friend-$action-{$other->getId()}", $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();
        $name = $other->getPseudo();

        try {
            match ($action) {
                'send' => $friendRequestLifecycle->send($user, $other),
                'remove' => $friendRequestLifecycle->removeFriend($user, $other),
                'block' => $friendRequestLifecycle->block($user, $other),
                'unblock' => $friendRequestLifecycle->unblock($user, $other),
            };

            $this->addFlash('success', match ($action) {
                'send' => $friendBookRepository->of($user)->hasFriend($other) ? "Vous êtes maintenant ami·es avec $name !" : "Votre demande a été envoyée à $name.",
                'remove' => "$name ne fait plus partie de vos ami·es.",
                'block' => "$name a été bloqué·e.",
                'unblock' => "$name a été débloqué·e.",
            });
        } catch (\LogicException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $refererRedirector->back($request, 'app_friend');
    }
}
