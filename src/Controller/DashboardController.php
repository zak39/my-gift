<?php

namespace App\Controller;

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

        $myLastParticipationGifts = $giftRepository->findGiftByParticipant($user, null);
        $myLastReservedParticipationGifts = $giftRepository->findGiftByParticipant($user, ParticipantStatus::RESERVED);
        $myLastParticipatingParticipationGifts = $giftRepository->findGiftByParticipant($user, ParticipantStatus::PARTICIPATING);

        $myGiftsCount = $giftRepository->count(['createdBy' => $user]);

        $myParticipationGiftsCount = $participantRepository->count(['participantUser' => $user]);
        $myReservedParticipationGiftsCount = $participantRepository->count(['participantUser' => $user, 'status' => ParticipantStatus::RESERVED]);
        $myContributingParticipationGiftsCount = $participantRepository->count(['participantUser' => $user, 'status' => ParticipantStatus::PARTICIPATING]);

        return $this->render('dashboard/index.html.twig', [
            'myLastGifts' => $myLastGifts,
            'myLastFavoritesGifts' => $myLastFavoritesGifts,
            'myGiftsCount' => $myGiftsCount,
            'myLastParticipationGifts' => $myLastParticipationGifts,
            'myLastReservedParticipationGifts' => $myLastReservedParticipationGifts,
            'myLastParticipatingParticipationGifts' => $myLastParticipatingParticipationGifts,
            'myParticipationGiftsCount' => $myParticipationGiftsCount,
            'myReservedParticipationGiftsCount' => $myReservedParticipationGiftsCount,
            'myContributingParticipationGiftsCount' => $myContributingParticipationGiftsCount,
        ]);
    }
}
