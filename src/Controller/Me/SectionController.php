<?php

namespace App\Controller\Me;

use App\Entity\Section;
use App\Entity\User;
use App\Form\SectionType;
use App\Repository\CrewRepository;
use App\Repository\GiftRepository;
use App\Repository\SectionRepository;
use App\Services\BreadcrumbBuilder;
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
 * The user's own list: its sections, the gifts filed in them or not, and the crews'
 * lists they take part in. A crew's sections are run from Crew\SectionController.
 */
#[IsGranted('IS_AUTHENTICATED')]
#[Route('/me/sections')]
final class SectionController extends AbstractController
{
    #[Route('', name: 'app_section')]
    public function index(GiftRepository $giftRepository, SectionRepository $sectionRepository, CrewRepository $crewRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('me/section/index.html.twig', [
            'giftsWithoutSection' => $giftRepository->findBy([
                'createdBy' => $user,
                'section' => null,
            ]),
            'sections' => $sectionRepository->findPersonalOf($user),
            'crews' => $crewRepository->findForMemberWithGiftCards($user),
            'user' => $user,
        ]);
    }

    #[Route('/new', name: 'app_section_new')]
    public function new(Request $request, CreateSection $createSection, BreadcrumbBuilder $breadcrumbBuilder): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $section = new Section();
        $section->setCreatedBy($user);

        $form = $this->createForm(SectionType::class, $section);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $createSection($section);

            $this->addFlash('success', "La section \"{$section->getTitle()}\" a bien été créée.");

            return $this->redirectToRoute('app_section');
        }

        return $this->render('section/new.html.twig', [
            'form' => $form,
            'section' => $section,
            'cancelUrl' => $this->generateUrl('app_section'),
            'breadcrumbs' => $breadcrumbBuilder->newSection(),
        ]);
    }

    #[IsGranted('section.is_owner', subject: 'section', statusCode: 404)]
    #[Route('/{id}/edit', name: 'app_section_edit', requirements: ['id' => '\d+'])]
    public function edit(#[MapEntity(expr: 'repository.findOneBy({"id": id, "crew": null})')] Section $section, Request $request, EntityManagerInterface $entityManager, BreadcrumbBuilder $breadcrumbBuilder): Response
    {
        $form = $this->createForm(SectionType::class, $section);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_section');
        }

        if ($form->isSubmitted()) {
            // handleRequest() wrote the rejected values into $section: reload the saved ones
            // for the page. The form still shows what was typed.
            $entityManager->refresh($section);
        }

        return $this->render('section/edit.html.twig', [
            'form' => $form,
            'section' => $section,
            'cancelUrl' => $this->generateUrl('app_section'),
            'breadcrumbs' => $breadcrumbBuilder->editSection($section),
        ]);
    }

    #[IsGranted('section.is_owner', subject: 'section', statusCode: 404)]
    #[IsCsrfTokenValid(new Expression('"section-delete-" ~ args["section"].getId()'))]
    #[Route('/{id}/delete', name: 'app_section_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(#[MapEntity(expr: 'repository.findOneBy({"id": id, "crew": null})')] Section $section, DeleteSection $deleteSection): Response
    {
        $deleteSection($section);

        $this->addFlash('success', "La section \"{$section->getTitle()}\" a bien été supprimée. Les cadeaux sont déplacés dans la section \"Cadeaux sans section\".");

        return $this->redirectToRoute('app_section');
    }
}
