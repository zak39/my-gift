<?php

namespace App\Controller\Me;

use App\Enum\ParticipantStatus;
use App\Enum\ParticipationPeriod;
use App\Repository\GiftRepository;
use App\Repository\ParticipantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED')]
#[Route('/me')]
final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(GiftRepository $giftRepository, ParticipantRepository $participantRepository): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // The user's own list: what they added to a crew's list belongs to the crew.
        $myLastGifts = $giftRepository->findPersonalOf($user, 5);
        $myLastFavoritesGifts = $giftRepository->findFavoritesGifts($user, 5);

        $myLastParticipationGifts = $giftRepository->findGiftByParticipant($user, null);
        // The tabs and figures say « en cours »: the gifts given or archived stay out, as on
        // /me/participations, where their « Voir tout » leads.
        $ongoing = ParticipationPeriod::ONGOING->giftStatuses();
        $myLastReservedParticipationGifts = $giftRepository->findGiftByParticipant($user, ParticipantStatus::RESERVED, $ongoing);
        $myLastParticipatingParticipationGifts = $giftRepository->findGiftByParticipant($user, ParticipantStatus::PARTICIPATING, $ongoing);

        $myGiftsCount = $giftRepository->countPersonalOf($user);

        $myParticipationGiftsCount = $participantRepository->count(['participantUser' => $user]);
        // The lists above carry no limit: their length is the figure.
        $myReservedParticipationGiftsCount = \count($myLastReservedParticipationGifts);
        $myContributingParticipationGiftsCount = \count($myLastParticipatingParticipationGifts);

        return $this->render('me/dashboard/index.html.twig', [
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
