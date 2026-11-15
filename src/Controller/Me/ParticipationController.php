<?php

namespace App\Controller\Me;

use App\Entity\User;
use App\Enum\ParticipationPeriod;
use App\Repository\GiftRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The gifts the user reserved or contributes to, one Molecule:Section per ParticipationPeriod.
 * Reserving or contributing is Gift\GiftReservationRequestController's and
 * Gift\GiftContributionRequestController's, from the gift.
 */
#[IsGranted('IS_AUTHENTICATED')]
#[Route('/me')]
final class ParticipationController extends AbstractController
{
    #[Route('/participations', name: 'app_participation', methods: ['GET'])]
    public function index(GiftRepository $giftRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $periods = [];
        foreach (ParticipationPeriod::cases() as $period) {
            $periods[] = [
                'period' => $period,
                'gifts' => $giftRepository->findParticipatedBy($user, $period->giftStatuses()),
            ];
        }

        return $this->render('me/participation/index.html.twig', [
            'periods' => $periods,
        ]);
    }
}
