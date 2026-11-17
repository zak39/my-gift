<?php

namespace App\Controller\Invitation;

use App\Entity\FriendBook;
use App\Entity\User;
use App\Repository\FriendBookRepository;
use App\Services\Friend\FindInvitation;
use App\Services\Lifecycle\FriendRequestLifecycle;
use App\Services\LinkPreviewBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * A user's invitation link, which they send themselves to the people they know: whoever
 * opens it may ask them as a friend, and they accept, refuse or block, as any request. The
 * only way to ask someone, so that nobody browses the users of the app.
 *
 * Public: a newcomer sees who invites them before creating an account, and a messenger
 * unfolds the link's card. An unknown slug answers 404, a slug changed since too: with a page
 * of its own on GET, saying where to find the new link. Each miss there counts against the
 * caller (FindInvitation), so that nobody tries words at full speed. The request checks its
 * token first: only a page shown holds one, so a POST tells no slug in use either.
 */
#[Route('/invitation/{slug}', requirements: ['slug' => FriendBook::INVITATION_SLUG_PATTERN])]
final class InvitationController extends AbstractController
{
    #[IsGranted('PUBLIC_ACCESS')]
    #[Route('', name: 'app_invitation', methods: ['GET'])]
    public function show(string $slug, Request $request, FindInvitation $findInvitation, LinkPreviewBuilder $linkPreviewBuilder): Response
    {
        $book = $findInvitation($slug, $request->getClientIp());

        // The card is for messengers, not search engines: NoIndexListener keeps a link posted
        // in public from getting its owner indexed.
        return null === $book
            ? $this->render('invitation/not_found.html.twig', response: new Response(status: Response::HTTP_NOT_FOUND))
            : $this->render('invitation/show.html.twig', [
                'owner' => $book->getOwner(),
                'slug' => $slug,
                'linkPreview' => $linkPreviewBuilder->forInvitation($book),
            ])
        ;
    }

    /**
     * A link with capitals, typed by hand or capitalized by a phone, leads to its slug, always
     * lowercase: one URL per link, the one whose card messengers cache (og:url).
     */
    #[IsGranted('PUBLIC_ACCESS')]
    #[Route('', name: 'app_invitation_lowercase', requirements: ['slug' => '(?=[^/]*[A-Z])(?i:'.FriendBook::INVITATION_SLUG_PATTERN.')'], methods: ['GET'], priority: 1)]
    public function lowercase(string $slug): Response
    {
        return $this->redirectToRoute('app_invitation', ['slug' => strtolower($slug)], Response::HTTP_MOVED_PERMANENTLY);
    }

    /**
     * Never on GET: messengers and browsers prefetch links. The lifecycle refuses what may
     * not be, one's own link, a block, a request already there, with its message.
     */
    #[IsGranted('IS_AUTHENTICATED')]
    #[IsCsrfTokenValid(new Expression('"invitation-send-" ~ args["slug"]'))]
    #[Route('', name: 'app_invitation_send', methods: ['POST'])]
    public function send(string $slug, FriendBookRepository $friendBookRepository, FriendRequestLifecycle $friendRequestLifecycle): Response
    {
        // Its page was shown, the token says so: not found, the slug changed since.
        $book = $friendBookRepository->findOneBySlug($slug) ?? throw $this->createNotFoundException();

        /** @var User $user */
        $user = $this->getUser();
        $owner = $book->getOwner();

        try {
            $friendRequestLifecycle->send($user, $owner);

            $this->addFlash('success', "Votre demande a été envoyée à {$owner->getPseudo()}.");
        } catch (\LogicException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('app_invitation', ['slug' => $slug]);
    }
}
