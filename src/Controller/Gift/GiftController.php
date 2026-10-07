<?php

namespace App\Controller\Gift;

use App\Entity\Gift;
use App\Entity\User;
use App\Services\BreadcrumbBuilder;
use App\Services\RefererRedirector;
use App\Services\Url\GiftOwnerUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * A gift's page as the friends who may offer it see it. Its path is linked from the gift
 * emails and shared with "Partager": it must not move. Its owners land on their own page
 * (Me\GiftController, Crew\GiftController), the same template seen from their list.
 */
#[IsGranted('IS_AUTHENTICATED')]
#[Route('/gift/{id}', requirements: ['id' => '\d+'])]
final class GiftController extends AbstractController
{
    #[IsGranted('gift.can_see', subject: 'gift')]
    #[Route('/show', name: 'app_gift_show')]
    public function show(Gift $gift, BreadcrumbBuilder $breadcrumbBuilder, GiftOwnerUrlGenerator $giftOwnerUrlGenerator, RefererRedirector $refererRedirector, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // An owner following a shared link or an email: to their own page of the gift.
        if ($gift->getOwnership()->includes($user)) {
            return $this->redirect($giftOwnerUrlGenerator->show($gift));
        }

        return $this->render('gift/show.html.twig', [
            'gift' => $gift,
            'breadcrumbs' => $breadcrumbBuilder->gift($gift),
            'btnReturn' => $refererRedirector->localReferer($request),
        ]);
    }
}
