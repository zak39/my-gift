<?php

namespace App\Controller\Me;

use App\Entity\User;
use App\Repository\GiftRepository;
use App\Services\FavoriteGiftGrouper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The gifts the user starred. Starring one is Gift\FavoriteController's, from the gift.
 */
#[IsGranted('IS_AUTHENTICATED')]
#[Route('/me')]
final class FavoriteController extends AbstractController
{
    #[Route('/favorites', name: 'app_gift_user_favorites', methods: ['GET'])]
    public function index(FavoriteGiftGrouper $favoriteGiftGrouper, GiftRepository $giftRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $gifts = $giftRepository->findFavoritesGifts($user);

        return $this->render('me/favorite/index.html.twig', [
            'giftCount' => \count($gifts),
            'favoriteGiftsByUsers' => $favoriteGiftGrouper->groupedByCreator($gifts),
        ]);
    }
}
