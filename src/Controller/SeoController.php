<?php

namespace App\Controller;

use App\Services\Seo\IndexablePages;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The two files search engines ask the site for, at its root. Generated rather than in
 * public/: they name the URL the robot asked for, and the preprod's differ from prod's. The
 * route's format gives the Content-Type.
 */
#[IsGranted('PUBLIC_ACCESS')]
final class SeoController extends AbstractController
{
    #[Route('/robots.txt', name: 'app_robots', format: 'txt', methods: ['GET'])]
    public function robots(IndexablePages $indexablePages): Response
    {
        return $this->render('seo/robots.txt.twig', [
            'indexed' => $indexablePages->isIndexed(),
        ]);
    }

    #[Route('/sitemap.xml', name: 'app_sitemap', format: 'xml', methods: ['GET'])]
    public function sitemap(IndexablePages $indexablePages): Response
    {
        return $this->render('seo/sitemap.xml.twig', [
            'urls' => $indexablePages->urls(),
        ]);
    }
}
