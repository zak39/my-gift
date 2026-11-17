<?php

namespace App\Controller\Me;

use App\Entity\FriendRequest;
use App\Entity\User;
use App\Form\InvitationSlugType;
use App\Repository\FriendBookRepository;
use App\Services\BreadcrumbBuilder;
use App\Services\Friend\CheckInvitationSlugAttempt;
use App\Services\Friend\InvitationSlugGenerator;
use App\Services\Lifecycle\FriendRequestLifecycle;
use App\Services\RefererRedirector;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The user's friends, the requests they sent or received, and their invitation link.
 * Acting on another user (unfriending, blocking) is User\FriendshipController's, asking
 * someone through their link Invitation\InvitationController's.
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
     * A slug of the user's choice: the previous link answers 404 at once, which the page
     * says before they save.
     */
    #[Route('/friends/invitation/edit', name: 'app_friend_invitation_edit')]
    public function editInvitation(Request $request, FriendBookRepository $friendBookRepository, CheckInvitationSlugAttempt $checkInvitationSlugAttempt, EntityManagerInterface $entityManager, BreadcrumbBuilder $breadcrumbBuilder): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $book = $friendBookRepository->of($user);
        $savedSlug = $book->getInvitationSlug();

        $form = $this->createForm(InvitationSlugType::class, $book);

        // Counted before handleRequest(), whose validation says whether the slug is taken.
        $refusal = $request->isMethod('POST') ? $checkInvitationSlugAttempt($user) : null;
        if (null === $refusal) {
            $form->handleRequest($request);
        } else {
            // RGAA 11.10: the refusal on the field, as its other errors.
            $form->get('invitationSlug')->addError(new FormError($refusal));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // Saved as it was: the link still works, no flash to say otherwise.
            if ($book->getInvitationSlug() !== $savedSlug) {
                $entityManager->flush();

                $this->addFlash('success', 'Votre lien d\'invitation a changé : l\'ancien ne fonctionne plus, envoyez le nouveau à vos ami·es.');
            }

            return $this->redirectToRoute('app_friend');
        }

        if ($form->isSubmitted()) {
            // handleRequest() wrote the rejected slug into $book: put the saved one back for the
            // page's current link. The form still shows what was typed, its own submitted data.
            $book->setInvitationSlug($savedSlug);
        }

        // A form refused unsubmitted gets no 422 from render(): a 429 says it, and Turbo shows
        // a POST's answer only if it is an error or a redirect.
        return $this->render('me/friend/invitation_edit.html.twig', [
            'form' => $form,
            'book' => $book,
            'breadcrumbs' => $breadcrumbBuilder->editInvitationLink(),
        ], null === $refusal ? null : new Response(status: Response::HTTP_TOO_MANY_REQUESTS));
    }

    /**
     * A new random slug, for a link that leaked: the previous one answers 404 at once. The
     * requests already received stay, to refuse or block as any other.
     */
    #[IsCsrfTokenValid('invitation-regenerate')]
    #[Route('/friends/invitation/regenerate', name: 'app_friend_invitation_regenerate', methods: ['POST'])]
    public function regenerateInvitation(FriendBookRepository $friendBookRepository, InvitationSlugGenerator $invitationSlugGenerator, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $friendBookRepository->of($user)->setInvitationSlug($invitationSlugGenerator());
        $entityManager->flush();

        $this->addFlash('success', 'Voici votre nouveau lien d\'invitation : l\'ancien ne fonctionne plus, envoyez celui-ci à vos ami·es.');

        return $this->redirectToRoute('app_friend');
    }

    /**
     * No voter on the route: the lifecycle checks can(), which runs the workflow guards,
     * which ask FriendRequestVoter. A forged request ends in an error message.
     *
     * $friendRequest nullable, the token checked first: without it, a 403 whether the id
     * exists or not, as FriendshipController::act().
     */
    #[IsCsrfTokenValid(new Expression('"friend-request-" ~ args["transition"] ~ "-" ~ args["id"]'))]
    #[Route('/friend-requests/{id}/{transition}', name: 'app_friend_request', requirements: ['id' => '\d+', 'transition' => 'accept|refuse|cancel'], methods: ['POST'])]
    public function answer(int $id, ?FriendRequest $friendRequest, string $transition, Request $request, FriendRequestLifecycle $friendRequestLifecycle, RefererRedirector $refererRedirector): Response
    {
        if (null === $friendRequest) {
            throw $this->createNotFoundException();
        }

        $senderName = $friendRequest->getSender()->getOwner()->getPseudo();
        $recipientName = $friendRequest->getRecipient()->getOwner()->getPseudo();

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
