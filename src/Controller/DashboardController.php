<?php

namespace App\Controller;

use App\Enum\GiftStatus;
use App\Enum\ParticipantStatus;
use App\Repository\GiftRepository;
use App\Repository\ParticipantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class DashboardController extends AbstractController
{
    #[IsGranted('IS_AUTHENTICATED')]
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(GiftRepository $giftRepository, ParticipantRepository $participantRepository): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $myLastGifts = $giftRepository->findBy(['createdBy' => $user], ['createdAt' => 'DESC'], 5);
        $myLastFavoritesGifts = $giftRepository->findFavoritesGifts($user, 5);
        $myLastContributingGifts = $giftRepository->findBy(['createdBy' => $user, 'status' => GiftStatus::CONTRIBUTING], ['createdAt' => 'DESC'], 5);
        $myLastReservedGifts = $giftRepository->findBy(['createdBy' => $user, 'status' => GiftStatus::RESERVED], ['createdAt' => 'DESC'], 5);

        $myLastParticipationGifts = $giftRepository->findGiftByParticipant($user, null);
        $myLastReservedParticipationGifts = $giftRepository->findGiftByParticipant($user, ParticipantStatus::RESERVED);
        $myLastParticipatingParticipationGifts = $giftRepository->findGiftByParticipant($user, ParticipantStatus::PARTICIPATING);

        $myGiftsCount = $giftRepository->count(['createdBy' => $user]);
        $myContributingGiftsCount = $giftRepository->count(['createdBy' => $user, 'status' => GiftStatus::CONTRIBUTING]);
        $myReservedGiftsCount = $giftRepository->count(['createdBy' => $user, 'status' => GiftStatus::RESERVED]);

        $myParticipationGiftsCount = $participantRepository->count(['participantUser' => $user]);
        $myReservedParticipationGiftsCount = $participantRepository->count(['participantUser' => $user, 'status' => ParticipantStatus::RESERVED]);
        $myContributingParticipationGiftsCount = $participantRepository->count(['participantUser' => $user, 'status' => ParticipantStatus::PARTICIPATING]);

        return $this->render('dashboard/index.html.twig', [
            'myLastGifts' => $myLastGifts,
            'myLastFavoritesGifts' => $myLastFavoritesGifts,
            'myLastContributingGifts' => $myLastContributingGifts,
            'myLastReservedGifts' => $myLastReservedGifts,
            'myGiftsCount' => $myGiftsCount,
            'myContributingGiftsCount' => $myContributingGiftsCount,
            'myReservedGiftsCount' => $myReservedGiftsCount,
            'myLastParticipationGifts' => $myLastParticipationGifts,
            'myLastReservedParticipationGifts' => $myLastReservedParticipationGifts,
            'myLastParticipatingParticipationGifts' => $myLastParticipatingParticipationGifts,
            'myParticipationGiftsCount' => $myParticipationGiftsCount,
            'myReservedParticipationGiftsCount' => $myReservedParticipationGiftsCount,
            'myContributingParticipationGiftsCount' => $myContributingParticipationGiftsCount,
        ]);
    }
}
