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
        $favoriteGiftsByList = $favoriteGiftGrouper->groupedByList($gifts);

        return $this->render('me/favorite/index.html.twig', [
            'giftCount' => \count($gifts),
            // The lists they come from, friends' and crews' apart for the page's summary.
            'friendCount' => \count(array_filter($favoriteGiftsByList, fn (array $list) => null !== $list['user'])),
            'crewCount' => \count(array_filter($favoriteGiftsByList, fn (array $list) => null !== $list['crew'])),
            'favoriteGiftsByList' => $favoriteGiftsByList,
        ]);
    }
}
