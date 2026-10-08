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
    #[Route('/sections', name: 'app_section_user_page')]
    public function index(User $user, GiftRepository $giftRepository, SectionRepository $sectionRepository, CrewRepository $crewRepository, BreadcrumbBuilder $breadcrumbBuilder, RefererRedirector $refererRedirector, Request $request): Response
    {
        if (!$this->isGranted('user.can_see_gifts', $user)) {
            // Neither sections nor gifts: what the template does not receive, it cannot show.
            return $this->render('user/section/locked.html.twig', [
                'user' => $user,
                'breadcrumbs' => $breadcrumbBuilder->userList($user),
            ]);
        }

        $giftsWithoutSection = [];
        if ($this->isGranted('user.is_owner', $user)) {
            $giftsWithoutSection = $giftRepository->findBy([
                'createdBy' => $user,
                'section' => null,
                'isPublic' => true,
            ]);
        }

        return $this->render('user/section/index.html.twig', [
            'giftsWithoutSection' => $giftsWithoutSection,
            'sections' => $sectionRepository->findPersonalOf($user),
            // Seeing the user's gifts, the viewer sees their crews' lists too: a member's
            // friends see the list.
            'crews' => $crewRepository->findForMemberWithGifts($user),
            'user' => $user,
            'breadcrumbs' => $breadcrumbBuilder->userList($user),
            'btnReturn' => $refererRedirector->localReferer($request) ?? $this->generateUrl('app_user'),
        ]);
    }
}
