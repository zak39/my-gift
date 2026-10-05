<?php

namespace App\Controller\Gift;

use App\Entity\Gift;
use App\Entity\User;
use App\Services\BreadcrumbBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * A gift's page, the same for its owners and for the friends who may offer it. Its path
 * is linked from the gift emails: it must not move.
 */
#[IsGranted('IS_AUTHENTICATED')]
#[Route('/gift/{id}', requirements: ['id' => '\d+'])]
final class GiftController extends AbstractController
{
    #[IsGranted('gift.can_see', subject: 'gift')]
    #[Route('/show', name: 'app_gift_show')]
    public function show(Gift $gift, BreadcrumbBuilder $breadcrumbBuilder, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('gift/show.html.twig', [
            'gift' => $gift,
            'breadcrumbs' => $breadcrumbBuilder->gift($gift, $user),
            'btnReturn' => $request->headers->get('referer'),
        ]);
    }
}
