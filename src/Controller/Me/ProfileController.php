<?php

namespace App\Controller\Me;

use App\Entity\User;
use App\Form\UserProfileType;
use App\Services\Lifecycle\UserLifecycle;
use App\Services\Security\CheckCurrentPassword;
use App\Services\User\ResetAvatar;
use App\Services\UserProfileService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/me')]
final class ProfileController extends AbstractController
{
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/profile', name: 'app_user_profile')]
    public function index(Request $request, UserProfileService $profileService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(UserProfileType::class, $user);
        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            return $this->render('me/profile/index.html.twig', [
                'profileForm' => $form->createView(),
                'user' => $user,
            ]);
        }

        $result = $profileService->processSubmission($form, $user, $request);

        if ($result->isValid()) {
            if ($result->shouldUseTurbo()) {
                $avatarStream = $this->renderView('turbo/avatar.turbo_stream.twig', [
                    'user' => $result->user(),
                ]);

                $flashStream = $this->renderView('turbo/success_flash.turbo_stream.twig');

                return new Response(
                    $avatarStream.$flashStream,
                    Response::HTTP_OK,
                    ['Content-Type' => 'text/vnd.turbo-stream.html']
                );
            }

            $this->addFlash('success', 'Votre profil a bien été mis à jour.');

            return $this->redirectToRoute('app_user_profile');
        }

        return $this->render('me/profile/index.html.twig', [
            'profileForm' => $form->createView(),
            'user' => $user,
        ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
    }

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[IsCsrfTokenValid('avatar-reset')]
    #[Route('/profile/avatar/reset', name: 'app_user_profile_avatar_reset', methods: ['POST'])]
    public function resetAvatar(ResetAvatar $resetAvatar): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($resetAvatar($user)) {
            $this->addFlash('success', 'Votre photo de profil a bien été réinitialisée.');
        } else {
            $this->addFlash('error', 'Aucun avatar n\'a pu être généré : réessayez plus tard.');
        }

        return $this->redirectToRoute('app_user_profile');
    }

    /**
     * The password again, on top of the CSRF token: a session left open on someone else's
     * computer is not enough to destroy an account.
     */
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[IsCsrfTokenValid('account-delete')]
    #[Route('/profile/delete', name: 'app_user_profile_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        CheckCurrentPassword $checkCurrentPassword,
        UserLifecycle $userLifecycle,
        Security $security,
        LoggerInterface $logger,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $refusal = $checkCurrentPassword($user, $request->request->getString('password'));
        if (null !== $refusal) {
            $this->addFlash('error', $refusal.' Votre compte n\'a pas été supprimé.');

            return $this->redirectToRoute('app_user_profile');
        }

        try {
            $userLifecycle->delete($user);
        } catch (\Throwable $exception) {
            // Rolled back: the account is intact. Logged, being caught, for whoever looks into it.
            $logger->error('Account deletion failed', ['user' => $user->getId(), 'exception' => $exception]);
            $this->addFlash('error', 'La suppression de votre compte n\'a pas pu aboutir : rien n\'a été supprimé. Réessayez plus tard ou contactez-nous.');

            return $this->redirectToRoute('app_user_profile');
        }

        // Invalidates the session and clears the remember-me cookie on the response it
        // returns, a redirection to the landing page.
        $response = $security->logout(false);

        // Added once logged out, so that it lands in the new session.
        $this->addFlash('success', 'Votre compte a bien été supprimé. Un email de confirmation vous a été envoyé.');

        return $response ?? $this->redirectToRoute('app_landing_page');
    }
}
