<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('PUBLIC_ACCESS')]
final class LandingPageController extends AbstractController
{
    #[Route('/', name: 'app_landing_page')]
    public function index(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        // A failed login redirects to app_login, which shows the error: nothing to read here.
        return $this->render('landing_page/index.html.twig');
    }
}
