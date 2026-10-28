<?php

namespace App\Controller\Gift;

use App\Entity\Gift;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Starring a gift from its card, in JSON for the heart button. The user's starred gifts
 * are listed by Me\FavoriteController.
 */
#[IsGranted('IS_AUTHENTICATED')]
#[Route('/gift/{id}/favorite', requirements: ['id' => '\d+'])]
final class FavoriteController extends AbstractController
{
    #[IsGranted('gift.can_see', subject: 'gift')]
    #[Route('', name: 'app_gift_add_favorite', methods: ['POST'])]
    public function add(Gift $gift, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->isCsrfTokenValid('gift-favorite', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();

        $user->addFavoriteGift($gift);
        $entityManager->flush();

        return new JsonResponse([
            'message' => sprintf('Le cadeau %s a été ajouté à vos favoris.', $gift->getTitle()),
        ]);
    }

    #[Route('/remove', name: 'app_gift_remove_favorite', methods: ['POST'])]
    public function remove(Gift $gift, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->isCsrfTokenValid('gift-favorite', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();

        $user->removeFavoriteGift($gift);
        $entityManager->flush();

        return new JsonResponse([
            'message' => sprintf('Le cadeau %s a été retiré à vos favoris.', $gift->getTitle()),
        ]);
    }
}
