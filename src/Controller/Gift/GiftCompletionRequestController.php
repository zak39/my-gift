<?php

namespace App\Controller\Gift;

use App\Entity\Gift;
use App\Entity\User;
use App\Services\Lifecycle\GiftLifecycle;
use App\Services\Url\GiftOwnerUrlGenerator;
use App\Services\Url\ViewerUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The end common to a reservation and a pool: the gift bought (by whoever took part),
 * then given and archived (by its owners). The same people Molecule:Gift:StatusButtons
 * shows the buttons to.
 */
#[IsGranted('IS_AUTHENTICATED')]
#[Route('/gift/{id}/request', requirements: ['id' => '\d+'])]
final class GiftCompletionRequestController extends AbstractController
{
    #[IsGranted(new Expression('subject.getParticipant(user) != null'), subject: 'gift')]
    #[Route('/ready', name: 'app_gift_ready', methods: ['POST'])]
    public function ready(Gift $gift, GiftLifecycle $giftLifecycle, ViewerUrlGenerator $viewerUrlGenerator): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $giftLifecycle->ready($gift, $user);

        return $this->redirect($viewerUrlGenerator->list($gift));
    }

    #[IsGranted('gift.is_owner', subject: 'gift')]
    #[Route('/given', name: 'app_gift_given', methods: ['POST'])]
    public function given(Gift $gift, GiftLifecycle $giftLifecycle, GiftOwnerUrlGenerator $giftOwnerUrlGenerator): Response
    {
        $giftLifecycle->given($gift);

        return $this->redirect($giftOwnerUrlGenerator->list($gift));
    }

    #[IsGranted('gift.is_owner', subject: 'gift')]
    #[Route('/archived', name: 'app_gift_archived', methods: ['POST'])]
    public function archived(Gift $gift, GiftLifecycle $giftLifecycle, GiftOwnerUrlGenerator $giftOwnerUrlGenerator): Response
    {
        $giftLifecycle->archived($gift);

        return $this->redirect($giftOwnerUrlGenerator->list($gift));
    }
}
