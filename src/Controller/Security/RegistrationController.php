<?php

namespace App\Controller\Security;

use App\Entity\User;
use App\Event\UserCreatedEvent;
use App\Form\RegistrationFormType;
use App\Form\ResetPasswordRequestFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Services\Security\ResendEmailConfirmation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

    #[IsGranted('PUBLIC_ACCESS')]
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EventDispatcherInterface $dispatcher, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
            $user->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($user);
            $entityManager->flush();

            $dispatcher->dispatch(new UserCreatedEvent($user));

            $this->addFlash('success', 'Bravo ! Votre compte a été créé. Encore une dernière étape, un mail vous a été envoyé où il faudra valider votre compte.');

            $this->emailVerifier->sendEmailConfirmation($user);

            return $this->redirectToRoute('app_register');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    /**
     * The link of the confirmation email, opened without being logged in: no account logs
     * in before it is confirmed (UserChecker). The link names its user, its signature
     * vouches for it. Every failure gets the same answer, an unknown id as a forged
     * signature: nobody learns which ids exist.
     */
    #[IsGranted('PUBLIC_ACCESS')]
    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($request->query->getInt('id'));

        try {
            if (null === $user) {
                throw new InvalidSignatureException();
            }

            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface) {
            $this->addFlash('verify_email_error', 'Ce lien de confirmation n\'est pas valide ou a expiré : demandez-en un nouveau.');

            return $this->redirectToRoute('app_verify_resend');
        }

        $this->addFlash('success', 'Votre adresse email est confirmée : vous pouvez vous connecter.');

        return $this->redirectToRoute('app_login');
    }

    /**
     * A new confirmation email, from the login page that refused an account not confirmed
     * yet, or from a link expired. The same answer for any address (ResendEmailConfirmation).
     */
    #[IsGranted('PUBLIC_ACCESS')]
    #[Route('/verify/resend', name: 'app_verify_resend')]
    public function resendEmailConfirmation(Request $request, ResendEmailConfirmation $resendEmailConfirmation): Response
    {
        // Only an address to ask: the reset password request's form does.
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $email */
            $email = $form->get('email')->getData();

            $refusal = $resendEmailConfirmation($email, $request->getClientIp());
            if (null !== $refusal) {
                $this->addFlash('verify_email_error', $refusal);

                return $this->redirectToRoute('app_verify_resend');
            }

            $this->addFlash('success', 'Si un compte attend encore sa confirmation à cette adresse, un nouvel email vient de lui être envoyé.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/resend_email_confirmation.html.twig', [
            'resendForm' => $form,
        ]);
    }
}
