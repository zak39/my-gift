<?php

namespace App\Controller\Security;

use App\Entity\FriendBook;
use App\Entity\User;
use App\Event\UserCreatedEvent;
use App\Form\RegistrationFormType;
use App\Form\ResetPasswordRequestFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Services\Security\ResendEmailConfirmation;
use App\Services\User\SaveUserPseudo;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use SymfonyCasts\Bundle\VerifyEmail\Exception\InvalidSignatureException;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    public function __construct(private EmailVerifier $emailVerifier)
    {
    }

    /**
     * $invitation: the slug of the invitation link that led here. The form posts it back in
     * its URL, and the confirmation email carries it, signed with the rest, to the login
     * (SecurityController), which leads back to the invitation: the email is often opened in
     * another browser, or on another device. A value that is no slug is ignored.
     */
    #[IsGranted('PUBLIC_ACCESS')]
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EventDispatcherInterface $dispatcher,
        SaveUserPseudo $saveUserPseudo,
        #[MapQueryParameter(options: ['regexp' => FriendBook::INVITATION_SLUG_REGEXP], flags: \FILTER_NULL_ON_FAILURE)] ?string $invitation = null,
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
            $user->setCreatedAt(new \DateTimeImmutable());

            // False when another took the same pseudo meanwhile: the form shows it, as UniqueEntity does.
            if (!$saveUserPseudo($user, $form->get('pseudo'))) {
                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form,
                    'invitation' => $invitation,
                ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
            }

            $dispatcher->dispatch(new UserCreatedEvent($user));

            $this->addFlash('success', 'Bravo ! Votre compte a été créé. Encore une dernière étape, un mail vous a été envoyé où il faudra valider votre compte.');

            $this->emailVerifier->sendEmailConfirmation($user, $invitation);

            return $this->redirectToRoute('app_register');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
            'invitation' => $invitation,
        ]);
    }

    /**
     * The link of the confirmation email, opened without being logged in: no account logs
     * in before it is confirmed (UserChecker). The link names its user, its signature
     * vouches for it. Every failure gets the same answer, an unknown id as a forged
     * signature: nobody learns which ids exist.
     *
     * The invitation the account was created from goes on to the login, or to a new email
     * when this link expired: a router-built path whichever way it came, harmless.
     */
    #[IsGranted('PUBLIC_ACCESS')]
    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(
        Request $request,
        UserRepository $userRepository,
        #[MapQueryParameter(options: ['regexp' => FriendBook::INVITATION_SLUG_REGEXP], flags: \FILTER_NULL_ON_FAILURE)] ?string $invitation = null,
    ): Response {
        $user = $userRepository->find($request->query->getInt('id'));

        try {
            if (null === $user) {
                throw new InvalidSignatureException();
            }

            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface) {
            $this->addFlash('verify_email_error', 'Ce lien de confirmation n\'est pas valide ou a expiré : demandez-en un nouveau.');

            return $this->redirectToRoute('app_verify_resend', ['invitation' => $invitation]);
        }

        $this->addFlash('success', 'Votre adresse email est confirmée : vous pouvez vous connecter.');

        return $this->redirectToRoute('app_login', ['invitation' => $invitation]);
    }

    /**
     * A new confirmation email, from the login page that refused an account not confirmed
     * yet, or from a link expired. The same answer for any address (ResendEmailConfirmation).
     * A link expired keeps its invitation: the form posts it back in its URL.
     */
    #[IsGranted('PUBLIC_ACCESS')]
    #[Route('/verify/resend', name: 'app_verify_resend')]
    public function resendEmailConfirmation(
        Request $request,
        ResendEmailConfirmation $resendEmailConfirmation,
        #[MapQueryParameter(options: ['regexp' => FriendBook::INVITATION_SLUG_REGEXP], flags: \FILTER_NULL_ON_FAILURE)] ?string $invitation = null,
    ): Response {
        // Only an address to ask: the reset password request's form does.
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $email */
            $email = $form->get('email')->getData();

            $refusal = $resendEmailConfirmation($email, $request->getClientIp(), $invitation);
            if (null !== $refusal) {
                $this->addFlash('verify_email_error', $refusal);

                return $this->redirectToRoute('app_verify_resend', ['invitation' => $invitation]);
            }

            $this->addFlash('success', 'Si un compte attend encore sa confirmation à cette adresse, un nouvel email vient de lui être envoyé.');

            return $this->redirectToRoute('app_login', ['invitation' => $invitation]);
        }

        return $this->render('registration/resend_email_confirmation.html.twig', [
            'resendForm' => $form,
        ]);
    }
}
