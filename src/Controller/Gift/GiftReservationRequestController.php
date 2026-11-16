<?php

namespace App\Controller\Gift;

use App\Entity\Gift;
use App\Entity\Participant;
use App\Entity\User;
use App\Form\ParticipantReserveType;
use App\Services\Exception\GiftActionRefusedException;
use App\Services\Lifecycle\GiftLifecycle;
use App\Services\Url\ViewerUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * A friend taking a gift on alone: reserving it, then backing out.
 */
#[IsGranted('IS_AUTHENTICATED')]
#[Route('/gift/{id}/request', requirements: ['id' => '\d+'])]
final class GiftReservationRequestController extends AbstractController
{
    #[IsGranted('gift.can_offer', subject: 'gift')]
    #[Route('/reserved', name: 'app_gift_reserved', methods: ['GET', 'POST'])]
    public function reserve(Gift $gift, Request $request, GiftLifecycle $giftLifecycle, ViewerUrlGenerator $viewerUrlGenerator): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $participant = new Participant();

        $form = $this->createForm(ParticipantReserveType::class, $participant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $giftLifecycle->reserved($gift, $participant, $user);
            } catch (GiftActionRefusedException $exception) {
                $this->addFlash('error', $exception->getMessage());
            }

            return $this->redirect($viewerUrlGenerator->list($gift));
        }

        return $this->render('gift/reserved.html.twig', [
            'form' => $form,
            'gift' => $gift,
        ]);
    }

    #[IsCsrfTokenValid(new Expression('"disengage-gift-" ~ args["gift"].getId()'))]
    #[Route('/disengage', name: 'app_gift_disengage', methods: ['POST'])]
    public function disengage(Gift $gift, GiftLifecycle $giftLifecycle, ViewerUrlGenerator $viewerUrlGenerator): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $giftLifecycle->disengage($gift, $user);
        } catch (GiftActionRefusedException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirect($viewerUrlGenerator->list($gift));
    }
}
