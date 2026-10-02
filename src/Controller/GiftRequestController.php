<?php

namespace App\Controller;

use App\Entity\Gift;
use App\Entity\Participant;
use App\Form\ParticipantContributingType;
use App\Form\ParticipantReserveType;
use App\Services\Lifecycle\GiftLifecycle;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED')]
#[Route('/gift/{id}/request', requirements: ['id' => '\d+'])]
final class GiftRequestController extends AbstractController
{
    #[IsGranted('gift.can_offer', subject: 'gift')]
    #[Route('/reserved', name: 'app_gift_reserved', methods: ['POST', 'GET'])]
    public function reserved(Gift $gift, Request $request, GiftLifecycle $giftLifecycle): Response
    {
        $participant = new Participant();

        $form = $this->createForm(ParticipantReserveType::class, $participant);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $giftLifecycle->reserved($gift, $participant, $this->getUser());

            return $this->redirectToList($gift);
        }

        return $this->render('gift/reserved.html.twig', [
            'form' => $form,
            'gift' => $gift,
        ]);
    }

    #[Route('/disengage', name: 'app_gift_disengage', methods: ['POST'])]
    public function disengage(Gift $gift, Request $request, GiftLifecycle $giftLifecycle): Response
    {
        if (!$this->isCsrfTokenValid('disengage-gift-'.$gift->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $giftLifecycle->disengage($gift, $this->getUser());

        return $this->redirectToList($gift);
    }

    #[Route('/ready', name: 'app_gift_ready', methods: ['POST'])]
    public function ready(Gift $gift, GiftLifecycle $giftLifecycle)
    {
        $giftLifecycle->ready($gift, $this->getUser());

        return $this->redirectToList($gift);
    }

    #[Route('/given', name: 'app_gift_given', methods: ['POST'])]
    public function given(Gift $gift, GiftLifecycle $giftLifecycle)
    {
        $giftLifecycle->given($gift, $this->getUser());

        return $this->redirectToList($gift);
    }

    #[Route('/archived', name: 'app_gift_archived', methods: ['POST'])]
    public function archived(Gift $gift, GiftLifecycle $giftLifecycle)
    {
        $giftLifecycle->archived($gift, $this->getUser());

        return $this->redirectToList($gift);
    }

    // A newcomer to the pool, or a contributor changing their share.
    #[IsGranted(new Expression('is_granted("gift.can_offer", subject) or subject.getParticipant(user) != null'), subject: 'gift')]
    #[Route('/participated', name: 'app_gift_participated', methods: ['GET', 'POST'])]
    public function participated(Gift $gift, GiftLifecycle $giftLifecycle, Request $request): Response
    {
        $participantExist = $gift->getParticipant($this->getUser());
        $participant = $participantExist ? $participantExist : new Participant();
        $participant->setGift($gift);

        $form = $this->createForm(ParticipantContributingType::class, $participant);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $giftLifecycle->participated($gift, $participant, $this->getUser());

            return $this->redirectToList($gift);
        }

        return $this->render('gift/contributing.html.twig', [
            'form' => $form,
            'gift' => $gift,
        ]);
    }

    #[Route('/cancelled-contribution', name: 'app_gift_cancelled_contribution', methods: ['POST'])]
    public function cancelledContribution(Gift $gift, GiftLifecycle $giftLifecycle, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('cancelled-contribution-gift-'.$gift->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $giftLifecycle->cancelledContribution($gift, $this->getUser());

        return $this->redirectToList($gift);
    }

    /**
     * Back to the list the gift is part of, where the buyer found it: its crew's page, or
     * its creator's. A participant may no longer see a crew's list (an ended friendship
     * must not trap them, see GiftVisibilityVoter): the creator's page then tells them
     * so, where the crew's would answer 403.
     */
    private function redirectToList(Gift $gift): Response
    {
        $crew = $gift->getCrew();

        return null !== $crew && $this->isGranted('crew.can_see_list', $crew)
            ? $this->redirectToRoute('app_crew_show', ['id' => $crew->getId()])
            : $this->redirectToRoute('app_section_user_page', ['id' => $gift->getCreatedBy()->getId()])
        ;
    }
}
