<?php

namespace App\Controller\Gift;

use App\Entity\Gift;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
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
    #[IsCsrfTokenValid('gift-favorite')]
    #[Route('', name: 'app_gift_add_favorite', methods: ['POST'])]
    public function add(Gift $gift, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $user->addFavoriteGift($gift);
        $entityManager->flush();

        return new JsonResponse([
            'message' => sprintf('Le cadeau %s a été ajouté à vos favoris.', $gift->getTitle()),
        ]);
    }

    #[IsCsrfTokenValid('gift-favorite')]
    #[Route('/remove', name: 'app_gift_remove_favorite', methods: ['POST'])]
    public function remove(Gift $gift, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $user->removeFavoriteGift($gift);
        $entityManager->flush();

        return new JsonResponse([
            'message' => sprintf('Le cadeau %s a été retiré à vos favoris.', $gift->getTitle()),
        ]);
    }
}
