<?php

namespace App\Controller\Crew;

use App\Entity\Crew;
use App\Entity\Section;
use App\Entity\User;
use App\Form\SectionType;
use App\Services\BreadcrumbBuilder;
use App\Services\RefererRedirector;
use App\Services\Section\CreateSection;
use App\Services\Section\DeleteSection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The sections of a crew's list, run by any of its members. The URL names the crew, and a
 * section is loaded through it: one of another crew is not found here, whatever its id.
 */
#[IsGranted('IS_AUTHENTICATED')]
#[IsGranted('crew.is_member', subject: 'crew')]
#[Route('/crews/{id}/sections', requirements: ['id' => '\d+'])]
final class SectionController extends AbstractController
{
    #[Route('/new', name: 'app_crew_section_new')]
    public function new(Crew $crew, Request $request, CreateSection $createSection, BreadcrumbBuilder $breadcrumbBuilder, RefererRedirector $refererRedirector): Response
    {
        // Back where it was asked from: the crew's page, or a member's list.
        $back = $refererRedirector->origin($request) ?? $this->generateUrl('app_crew_show', ['id' => $crew->getId()]);

        /** @var User $user */
        $user = $this->getUser();
        $section = new Section();
        $section->setCreatedBy($user);
        $section->setCrew($crew);

        $form = $this->createForm(SectionType::class, $section);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $createSection($section);

            $this->addFlash('success', "La section \"{$section->getTitle()}\" a bien été créée.");

            return $this->redirect($back);
        }

        return $this->render('section/new.html.twig', [
            'form' => $form,
            'section' => $section,
            'back' => $back,
            'cancelUrl' => $back,
            'breadcrumbs' => $breadcrumbBuilder->newCrewSection($crew),
        ]);
    }

    #[Route('/{section}/edit', name: 'app_crew_section_edit', requirements: ['section' => '\d+'])]
    public function edit(Crew $crew, #[MapEntity(expr: 'repository.findOneBy({"id": section, "crew": id})')] Section $section, Request $request, EntityManagerInterface $entityManager, BreadcrumbBuilder $breadcrumbBuilder, RefererRedirector $refererRedirector): Response
    {
        // Back where it was asked from: the crew's page, or a member's list.
        $back = $refererRedirector->origin($request) ?? $this->generateUrl('app_crew_show', ['id' => $crew->getId()]);
        $form = $this->createForm(SectionType::class, $section);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirect($back);
        }

        if ($form->isSubmitted()) {
            // handleRequest() wrote the rejected values into $section: reload the saved ones
            // for the page. The form still shows what was typed.
            $entityManager->refresh($section);
        }

        return $this->render('section/edit.html.twig', [
            'form' => $form,
            'section' => $section,
            'back' => $back,
            'cancelUrl' => $back,
            'breadcrumbs' => $breadcrumbBuilder->editCrewSection($section),
        ]);
    }

    #[IsCsrfTokenValid(new Expression('"section-delete-" ~ args["section"].getId()'))]
    #[Route('/{section}/delete', name: 'app_crew_section_delete', requirements: ['section' => '\d+'], methods: ['DELETE'])]
    public function delete(Crew $crew, #[MapEntity(expr: 'repository.findOneBy({"id": section, "crew": id})')] Section $section, Request $request, DeleteSection $deleteSection, RefererRedirector $refererRedirector): Response
    {
        $deleteSection($section);

        // Out of any section, a gift is its author's: those of a crew's list leave it.
        $this->addFlash('success', "La section \"{$section->getTitle()}\" a bien été supprimée. Ses cadeaux sont retournés dans les « Cadeaux sans section » de celles et ceux qui les avaient ajoutés.");

        // The list its modal sat on: the crew's page, or a member's.
        return $this->redirect($refererRedirector->origin($request) ?? $this->generateUrl('app_crew_show', ['id' => $crew->getId()]));
    }
}
