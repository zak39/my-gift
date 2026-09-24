<?php

namespace App\Controller;

use App\Entity\Section;
use App\Entity\User;
use App\Form\SectionType;
use App\Repository\GiftRepository;
use App\Services\BreadcrumbBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED')]
final class SectionController extends AbstractController
{
    #[IsGranted('user.is_owner', subject: 'user')]
    #[Route('/section/{id}', requirements: ['id' => '\d+'], name: 'app_section')]
    public function index(GiftRepository $giftRepository, User $user): Response
    {
        $giftsWithoutSection = $giftRepository->findBy([
            'createdBy' => $user,
            'section' => null,
        ]);

        return $this->render('section/index.html.twig', [
            'giftsWithoutSection' => $giftsWithoutSection,
            'sections' => $user->getSections(),
            'user' => $user,
        ]);
    }

    #[Route('/section/new', name: 'app_section_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $section = new Section();
        /** @var User */
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

            $this->addFlash('success', "La section \"{$section->getTitle()}\" a bien été créée.");

            return $this->redirectToRoute('app_section', ['id' => $user->getId()]);
        }

        return $this->render('section/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/section/{id}/edit', name: 'app_section_edit')]
    public function edit(Request $request, Section $section, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('section.is_creator', $section);

        $form = $this->createForm(SectionType::class, $section);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($section);
            $entityManager->flush();

            /** @var User */
            $user = $this->getUser();

            return $this->redirectToRoute('app_section', ['id' => $user->getId()]);
        }

        return $this->render('section/edit.html.twig', [
            'form' => $form,
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/section/{id}/delete', name: 'app_section_delete', methods: ['DELETE'])]
    public function delete(Section $section, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('section.is_creator', $section);

        foreach ($section->getGifts() as $gift) {
            $section->removeGift($gift);
        }

        $entityManager->remove($section);
        $entityManager->flush();

        $this->addFlash('success', "La section \"{$section->getTitle()}\" a bien été supprimée. Les cadeaux sont déplacés dans la section \"Cadeaux sans section\".");

        /** @var User */
        $user = $this->getUser();

        return $this->redirectToRoute('app_section', ['id' => $user->getId()]);
    }

    #[Route('/users/{id}/sections', requirements: ['id' => '\d+'], name: 'app_section_user_page')]
    public function userPage(GiftRepository $giftRepository, User $user, BreadcrumbBuilder $breadcrumbBuilder, Request $request): Response
    {
        $giftsWithoutSection = [];
        if ($this->isGranted('user.is_owner', $user)) {
            $giftsWithoutSection = $giftRepository->findBy([
                'createdBy' => $user,
                'section' => null,
                'isPublic' => true,
            ]);
        }

        return $this->render('section/user_page.html.twig', [
            'giftsWithoutSection' => $giftsWithoutSection,
            'sections' => $user->getSections(),
            'user' => $user,
            'breadcrumbs' => $breadcrumbBuilder->giftFromUserSection($user),
            'btnReturn' => $request->headers->get('referer'),
        ]);
    }
}
