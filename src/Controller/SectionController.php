<?php

namespace App\Controller;

use App\Entity\Section;
use App\Form\SectionType;
use App\Repository\GiftRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SectionController extends AbstractController
{
    #[Route('/section', name: 'app_section')]
    public function index(GiftRepository $giftRepository): Response
    {
        $user = $this->getUser();
        $giftsWithoutSection = $giftRepository->findBy([
            "createdBy" => $this->getUser(),
            "section" => null
        ]);

        return $this->render('section/index.html.twig', [
            'giftsWithoutSection' => $giftsWithoutSection,
            'sections' => $user->getSections(),
        ]);
    }

    #[Route('/section/new', name: 'app_section_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $section = new Section();
        $user = $this->getUser();

        $form = $this->createForm(SectionType::class, $section);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $section->setCreatedBy($user);

            $title = $section->getTitle();
            $slug = str_replace(' ', '-', $title);
            $slug = strtolower($slug);
            $section->setSlug($slug);

            $entityManager->persist($section);
            $entityManager->flush();

            $this->addFlash("success", "La section \"{$section->getTitle()}\" a bien été créée.");
            
            return $this->redirectToRoute('app_section');
        }

        return $this->render('section/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/section/{id}/edit', name: 'app_section_edit')]
    public function edit(Request $request, Section $section, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SectionType::class, $section);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($section);
            $entityManager->flush();

            return $this->redirectToRoute('app_section');
        }

        return $this->render('section/edit.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/section/{id}/delete', name: 'app_section_delete')]
    public function delete(Section $section, EntityManagerInterface $entityManager): Response
    {
        foreach ($section->getGifts() as $gift)
        {
            $section->removeGift($gift);
        }

        $entityManager->remove($section);
        $entityManager->flush();

        $this->addFlash("success", "La section \"{$section->getTitle()}\" a bien été supprimée. Les cadeaux sont déplacés dans la section \"Cadeaux sans section\".");

        return $this->redirectToRoute('app_section');
    }
}
