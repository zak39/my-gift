<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Repository\CrewRepository;
use App\Repository\GiftRepository;
use App\Repository\SectionRepository;
use App\Services\BreadcrumbBuilder;
use App\Services\RefererRedirector;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * A user's list as their friends see it, published gifts only. Its owner previews it here
 * too ("Voir mon profil public"); they run it from Me\SectionController.
 */
#[IsGranted('IS_AUTHENTICATED')]
#[Route('/users/{id}', requirements: ['id' => '\d+'])]
final class SectionController extends AbstractController
{
    /**
     * For anyone but the user and their friends, a 404, as for an id that does not exist: the
     * ids follow each other, and a page saying "private" would let anyone list the accounts.
     *
     * $user is nullable so that a missing id is not a 404 before #[IsGranted] runs: the
     * class's IS_AUTHENTICATED sends a visitor logged out to log in whether the account
     * exists or not, then the voter, which grants nothing on null, answers the 404.
     */
    #[IsGranted('user.can_see_gifts', subject: 'user', statusCode: 404)]
    #[Route('/sections', name: 'app_section_user_page')]
    public function index(?User $user, GiftRepository $giftRepository, SectionRepository $sectionRepository, CrewRepository $crewRepository, BreadcrumbBuilder $breadcrumbBuilder, RefererRedirector $refererRedirector, Request $request): Response
    {
        return $this->render('user/section/index.html.twig', [
            'giftsWithoutSection' => $giftRepository->findBy(
                [
                    'createdBy' => $user,
                    'section' => null,
                    'isPublic' => true,
                ],
                ['createdAt' => 'DESC'],
            ),
            'sections' => $sectionRepository->findPersonalOf($user),
            // Seeing the user's gifts, the viewer sees their crews' lists too: a member's
            // friends see the list.
            'crews' => $crewRepository->findForMemberWithGiftCards($user),
            'user' => $user,
            'breadcrumbs' => $breadcrumbBuilder->userList($user),
            'btnReturn' => $refererRedirector->localReferer($request) ?? $this->generateUrl('app_friend'),
        ]);
    }
}
