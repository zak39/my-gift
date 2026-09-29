<?php

namespace App\Controller;

use App\Entity\FriendRequest;
use App\Entity\User;
use App\Repository\FriendBookRepository;
use App\Services\Lifecycle\FriendRequestLifecycle;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED')]
final class FriendController extends AbstractController
{
    #[Route('/friends', name: 'app_friend')]
    public function index(FriendBookRepository $friendBookRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('friend/index.html.twig', [
            'book' => $friendBookRepository->of($user),
        ]);
    }

    #[Route('/users/{id}/friend/{action}', name: 'app_friend_action', requirements: ['id' => '\d+', 'action' => 'send|remove|block|unblock'], methods: ['POST'])]
    public function userAction(User $other, string $action, Request $request, FriendRequestLifecycle $friendRequestLifecycle, FriendBookRepository $friendBookRepository): Response
    {
        if (!$this->isCsrfTokenValid("friend-$action-{$other->getId()}", $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();
        $name = $other->getFirstname();

        try {
            match ($action) {
                'send' => $friendRequestLifecycle->send($user, $other),
                'remove' => $friendRequestLifecycle->removeFriend($user, $other),
                'block' => $friendRequestLifecycle->block($user, $other),
                'unblock' => $friendRequestLifecycle->unblock($user, $other),
            };

            $message = match ($action) {
                'send' => $friendBookRepository->of($user)->hasFriend($other) ? "Vous êtes maintenant ami·es avec $name !" : "Votre demande a été envoyée à $name.",
                'remove' => "$name ne fait plus partie de vos ami·es.",
                'block' => "$name a été bloqué·e.",
                'unblock' => "$name a été débloqué·e.",
            };

            $this->addFlash('success', $message);
        } catch (\LogicException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectBack($request);
    }

    /**
     * No voter on the route: the manager checks can(), which runs the workflow guards,
     * which ask FriendRequestVoter. A forged request ends in an error message.
     */
    #[Route('/friend-request/{id}/{transition}', name: 'app_friend_request', requirements: ['id' => '\d+', 'transition' => 'accept|refuse|cancel'], methods: ['POST'])]
    public function answer(FriendRequest $friendRequest, string $transition, Request $request, FriendRequestLifecycle $friendRequestLifecycle): Response
    {
        if (!$this->isCsrfTokenValid("friend-request-$transition-{$friendRequest->getId()}", $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $senderName = $friendRequest->getSender()->getOwner()->getFirstname();
        $recipientName = $friendRequest->getRecipient()->getOwner()->getFirstname();

        try {
            match ($transition) {
                'accept' => $friendRequestLifecycle->accept($friendRequest),
                'refuse' => $friendRequestLifecycle->refuse($friendRequest),
                'cancel' => $friendRequestLifecycle->cancel($friendRequest),
            };

            $this->addFlash('success', match ($transition) {
                'accept' => "Vous êtes maintenant ami·es avec $senderName !",
                'refuse' => "La demande de $senderName a été refusée.",
                'cancel' => "Votre demande à $recipientName a été annulée.",
            });
        } catch (\LogicException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectBack($request);
    }

    /**
     * Back to the page the button was on: the search keeps its query and page in the URL.
     * The referer comes from the client, hence the same-site check before following it.
     */
    private function redirectBack(Request $request): Response
    {
        $referer = $request->headers->get('referer');

        if (null !== $referer && str_starts_with($referer, $request->getSchemeAndHttpHost().'/')) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_friend');
    }
}
