<?php

namespace App\Controller\Security;

use App\Entity\FriendBook;
use App\Security\Exception\UnverifiedEmailException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class SecurityController extends AbstractController
{
    use TargetPathTrait;

    /**
     * $invitation: the slug of the invitation link that led here, from its page or from the
     * confirmation email of an account created from it (RegistrationController). Logging in
     * leads back to it. Only the slug travels, and the path is built from the route: nothing
     * taken from the request can make it lead elsewhere. A value that is no slug is ignored.
     */
    #[Route(path: '/login', name: 'app_login')]
    public function login(
        AuthenticationUtils $authenticationUtils,
        Request $request,
        #[MapQueryParameter(options: ['regexp' => FriendBook::INVITATION_SLUG_REGEXP], flags: \FILTER_NULL_ON_FAILURE)] ?string $invitation = null,
    ): Response {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return null !== $invitation
                ? $this->redirectToRoute('app_invitation', ['slug' => $invitation])
                : $this->redirectToRoute('app_dashboard')
            ;
        }

        $session = $request->getSession();

        if (null !== $invitation) {
            // In the session rather than the form's _target_path: a mistyped password brings
            // the form back at /login, without the query string, and the session still holds it.
            // Over any page saved before: the user comes from the invitation, just clicked.
            $this->saveTargetPath($session, 'main', $this->generateUrl('app_invitation', ['slug' => $invitation]));
        } elseif (preg_match('#^/invitation/('.FriendBook::INVITATION_SLUG_PATTERN.')$#D', (string) parse_url((string) $this->getTargetPath($session, 'main'), \PHP_URL_PATH), $matches)) {
            // Back at /login after a refused login, the query gone: the slug from the saved
            // invitation, for the links to a new confirmation email and to the registration.
            $invitation = $matches[1];
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            // Not an error but a step left: a warning, with the link to a new email.
            'emailUnverified' => $error instanceof UnverifiedEmailException,
            'invitation' => $invitation,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
