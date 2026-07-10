<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserProfileType;
use App\Services\UserProfileService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserProfileController extends AbstractController
{
    #[Route('/user/profile', name: 'app_user_profile')]
    public function index(Request $request, UserProfileService $profileService): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $form = $this->createForm(UserProfileType::class, $user);
        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            return $this->render('user_profile/index.html.twig', [
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

        return $this->render('user_profile/index.html.twig', [
            'profileForm' => $form->createView(),
            'user' => $user,
        ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
    }
}
