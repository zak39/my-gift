<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

/**
 * The email confirming a new account's address, which UserChecker requires before logging
 * in. Its link carries the user's id: they confirm without being logged in, which they
 * cannot be yet (the bundle's anonymous validation).
 */
class EmailVerifier
{
    public function __construct(
        private VerifyEmailHelperInterface $verifyEmailHelper,
        private MailerInterface $mailer,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * At registration, and again on request (ResendEmailConfirmation).
     *
     * @param string|null $invitation the slug of the invitation link the account was created
     *                                from: the link carries it, signed with the rest, so that
     *                                the login leads back to it (none: left out)
     */
    public function sendEmailConfirmation(User $user, ?string $invitation = null): void
    {
        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            'app_verify_email',
            (string) $user->getId(),
            (string) $user->getEmail(),
            ['id' => $user->getId(), 'invitation' => $invitation],
        );

        $email = (new TemplatedEmail())
            ->to((string) $user->getEmail())
            ->subject('Confirmation de votre adresse e-mail sur My Gift')
            ->htmlTemplate('emails/welcome.html.twig')
            ->context([
                'fullName' => $user->getFullName(),
                'signedUrl' => $signatureComponents->getSignedUrl(),
                'expiresAtMessageKey' => $signatureComponents->getExpirationMessageKey(),
                'expiresAtMessageData' => $signatureComponents->getExpirationMessageData(),
            ])
        ;

        $this->mailer->send($email);
    }

    /**
     * @throws VerifyEmailExceptionInterface
     */
    public function handleEmailConfirmation(Request $request, User $user): void
    {
        $this->verifyEmailHelper->validateEmailConfirmationFromRequest($request, (string) $user->getId(), (string) $user->getEmail());

        $user->setIsVerified(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
