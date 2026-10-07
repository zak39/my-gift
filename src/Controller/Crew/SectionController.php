<?php

namespace App\Controller\Crew;

use App\Entity\Crew;
use App\Entity\Section;
use App\Entity\User;
use App\Form\SectionType;
use App\Services\BreadcrumbBuilder;
use App\Services\Section\CreateSection;
use App\Services\Section\DeleteSection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
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
    public function new(Crew $crew, Request $request, CreateSection $createSection, BreadcrumbBuilder $breadcrumbBuilder): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $section = (new Section())->setCreatedBy($user)->setCrew($crew);

        $form = $this->createForm(SectionType::class, $section);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $createSection($section);

            $this->addFlash('success', "La section \"{$section->getTitle()}\" a bien été créée.");

            return $this->redirectToRoute('app_crew_show', ['id' => $crew->getId()]);
        }

        return $this->render('section/new.html.twig', [
            'form' => $form,
            'section' => $section,
            'cancelUrl' => $this->generateUrl('app_crew_show', ['id' => $crew->getId()]),
            'breadcrumbs' => $breadcrumbBuilder->newCrewSection($crew),
        ]);
    }

    #[Route('/{section}/edit', name: 'app_crew_section_edit', requirements: ['section' => '\d+'])]
    public function edit(Crew $crew, #[MapEntity(expr: 'repository.findOneBy({"id": section, "crew": id})')] Section $section, Request $request, EntityManagerInterface $entityManager, BreadcrumbBuilder $breadcrumbBuilder): Response
    {
        $form = $this->createForm(SectionType::class, $section);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_crew_show', ['id' => $crew->getId()]);
        }

        if ($form->isSubmitted()) {
            // handleRequest() wrote the rejected values into $section: reload the saved ones
            // for the page. The form still shows what was typed.
            $entityManager->refresh($section);
        }

        return $this->render('section/edit.html.twig', [
            'form' => $form,
            'section' => $section,
            'cancelUrl' => $this->generateUrl('app_crew_show', ['id' => $crew->getId()]),
            'breadcrumbs' => $breadcrumbBuilder->editCrewSection($section),
        ]);
    }

    #[Route('/{section}/delete', name: 'app_crew_section_delete', requirements: ['section' => '\d+'], methods: ['DELETE'])]
    public function delete(Crew $crew, #[MapEntity(expr: 'repository.findOneBy({"id": section, "crew": id})')] Section $section, Request $request, DeleteSection $deleteSection): Response
    {
        if (!$this->isCsrfTokenValid("section-delete-{$section->getId()}", $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $deleteSection($section);

        // Out of any section, a gift is its author's: those of a crew's list leave it.
        $this->addFlash('success', "La section \"{$section->getTitle()}\" a bien été supprimée. Ses cadeaux sont retournés dans les « Cadeaux sans section » de celles et ceux qui les avaient ajoutés.");

        return $this->redirectToRoute('app_crew_show', ['id' => $crew->getId()]);
    }
}
