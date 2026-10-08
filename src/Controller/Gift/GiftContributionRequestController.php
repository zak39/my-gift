<?php

namespace App\Controller\Gift;

use App\Entity\Gift;
use App\Entity\Participant;
use App\Entity\User;
use App\Form\ParticipantContributingType;
use App\Services\Exception\GiftActionRefusedException;
use App\Services\Lifecycle\GiftLifecycle;
use App\Services\Url\ViewerUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Friends chipping in together: joining the pool, changing one's share, withdrawing it.
 */
#[IsGranted('IS_AUTHENTICATED')]
#[Route('/gift/{id}/request', requirements: ['id' => '\d+'])]
final class GiftContributionRequestController extends AbstractController
{
    // A newcomer to the pool, or a contributor changing their share.
    #[IsGranted(new Expression('is_granted("gift.can_offer", subject) or subject.getParticipant(user) != null'), subject: 'gift')]
    #[Route('/participated', name: 'app_gift_participated', methods: ['GET', 'POST'])]
    public function participate(Gift $gift, GiftLifecycle $giftLifecycle, ViewerUrlGenerator $viewerUrlGenerator, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $participant = $gift->getParticipant($user) ?? new Participant();
        $participant->setGift($gift);

        $form = $this->createForm(ParticipantContributingType::class, $participant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $giftLifecycle->participated($gift, $participant, $user);
            } catch (GiftActionRefusedException $exception) {
                $this->addFlash('error', $exception->getMessage());
            }

            return $this->redirect($viewerUrlGenerator->list($gift));
        }

        return $this->render('gift/contributing.html.twig', [
            'form' => $form,
            'gift' => $gift,
        ]);
    }

    #[Route('/cancelled-contribution', name: 'app_gift_cancelled_contribution', methods: ['POST'])]
    public function cancelContribution(Gift $gift, GiftLifecycle $giftLifecycle, ViewerUrlGenerator $viewerUrlGenerator, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('cancelled-contribution-gift-'.$gift->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();

        try {
            $giftLifecycle->cancelledContribution($gift, $user);
        } catch (GiftActionRefusedException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirect($viewerUrlGenerator->list($gift));
    }
}
