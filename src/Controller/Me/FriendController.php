<?php

namespace App\Controller\Me;

use App\Entity\FriendRequest;
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
 * The user's friends and the requests they sent or received. Acting on another user
 * (sending a request, blocking) is User\FriendshipController's.
 */
#[IsGranted('IS_AUTHENTICATED')]
#[Route('/me')]
final class FriendController extends AbstractController
{
    #[Route('/friends', name: 'app_friend')]
    public function index(FriendBookRepository $friendBookRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('me/friend/index.html.twig', [
            'book' => $friendBookRepository->of($user),
        ]);
    }

    /**
     * No voter on the route: the manager checks can(), which runs the workflow guards,
     * which ask FriendRequestVoter. A forged request ends in an error message.
     */
    #[Route('/friend-requests/{id}/{transition}', name: 'app_friend_request', requirements: ['id' => '\d+', 'transition' => 'accept|refuse|cancel'], methods: ['POST'])]
    public function answer(FriendRequest $friendRequest, string $transition, Request $request, FriendRequestLifecycle $friendRequestLifecycle, RefererRedirector $refererRedirector): Response
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

        return $refererRedirector->back($request, 'app_friend');
    }
}
