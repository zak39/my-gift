<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Services\Lifecycle\FriendRequestLifecycle;
use App\Services\RefererRedirector;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * What the user does to another one they are linked to: unfriend, block, unblock. Asking
 * someone as a friend goes through their invitation link (Invitation\InvitationController),
 * never by id; answering a request is Me\FriendController's.
 */
#[IsGranted('IS_AUTHENTICATED')]
#[Route('/users/{id}', requirements: ['id' => '\d+'])]
final class FriendshipController extends AbstractController
{
    // The token before the user, as SectionController does with its voter: a missing id
    // must not answer otherwise than an existing one, to whoever has no token for it.
    #[IsCsrfTokenValid(new Expression('"friend-" ~ args["action"] ~ "-" ~ args["id"]'))]
    #[Route('/friend/{action}', name: 'app_friend_action', requirements: ['action' => 'remove|block|unblock'], methods: ['POST'])]
    public function act(int $id, ?User $other, string $action, Request $request, FriendRequestLifecycle $friendRequestLifecycle, RefererRedirector $refererRedirector): Response
    {
        if (null === $other) {
            throw $this->createNotFoundException();
        }

        /** @var User $user */
        $user = $this->getUser();
        $name = $other->getPseudo();

        try {
            match ($action) {
                'remove' => $friendRequestLifecycle->removeFriend($user, $other),
                'block' => $friendRequestLifecycle->block($user, $other),
                'unblock' => $friendRequestLifecycle->unblock($user, $other),
            };

            $this->addFlash('success', match ($action) {
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
