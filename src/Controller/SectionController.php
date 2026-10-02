<?php

namespace App\Controller;

use App\Entity\Section;
use App\Entity\User;
use App\Form\SectionType;
use App\Repository\CrewRepository;
use App\Repository\GiftRepository;
use App\Repository\SectionRepository;
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
    public function index(GiftRepository $giftRepository, SectionRepository $sectionRepository, CrewRepository $crewRepository, User $user): Response
    {
        $giftsWithoutSection = $giftRepository->findBy([
            'createdBy' => $user,
            'section' => null,
        ]);

        return $this->render('section/index.html.twig', [
            'giftsWithoutSection' => $giftsWithoutSection,
            'sections' => $sectionRepository->findPersonalOf($user),
            'crews' => $crewRepository->findForMemberWithGifts($user),
            'user' => $user,
        ]);
    }

    #[Route('/section/new', name: 'app_section_new')]
    public function new(Request $request, EntityManagerInterface $entityManager, BreadcrumbBuilder $breadcrumbBuilder, CrewRepository $crewRepository): Response
    {
        /** @var User */
        $user = $this->getUser();
        $section = (new Section())->setCreatedBy($user);

        // "Créer une section" on a crew's page preselects the crew.
        $crew = $crewRepository->find($request->query->getInt('crew'));
        if (null !== $crew && $this->isGranted('crew.is_member', $crew)) {
            $section->setCrew($crew);
        }

        $form = $this->createForm(SectionType::class, $section, [
            'crews' => $crewRepository->findForMember($user),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $title = $section->getTitle();
            $slug = str_replace(' ', '-', $title);
            $slug = strtolower($slug);
            $section->setSlug($slug);

            $entityManager->persist($section);
            $entityManager->flush();

            $this->addFlash('success', "La section \"{$section->getTitle()}\" a bien été créée.");

            return $this->redirectToList($section);
        }

        return $this->render('section/new.html.twig', [
            'form' => $form,
            'section' => $section,
            'breadcrumbs' => $breadcrumbBuilder->newSection($section),
        ]);
    }

    #[Route('/section/{id}/edit', name: 'app_section_edit')]
    public function edit(Request $request, Section $section, EntityManagerInterface $entityManager, BreadcrumbBuilder $breadcrumbBuilder): Response
    {
        $this->denyAccessUnlessGranted('section.is_owner', $section);

        $form = $this->createForm(SectionType::class, $section);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($section);
            $entityManager->flush();

            return $this->redirectToList($section);
        }

        if ($form->isSubmitted()) {
            // handleRequest() wrote the rejected values into $section: reload the saved ones for the
            // page. The form still shows what was typed, since it keeps its own submitted data.
            $entityManager->refresh($section);
        }

        return $this->render('section/edit.html.twig', [
            'form' => $form,
            'section' => $section,
            'breadcrumbs' => $breadcrumbBuilder->editSection($section),
        ]);
    }

    #[Route('/section/{id}/delete', name: 'app_section_delete', methods: ['DELETE'])]
    public function delete(Section $section, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('section.is_owner', $section);

        $section->releaseGifts();
        $entityManager->remove($section);
        $entityManager->flush();

        // Out of any section, a gift is its author's: those of a crew's list leave it.
        $this->addFlash('success', null !== $section->getCrew()
            ? "La section \"{$section->getTitle()}\" a bien été supprimée. Ses cadeaux sont retournés dans les « Cadeaux sans section » de celles et ceux qui les avaient ajoutés."
            : "La section \"{$section->getTitle()}\" a bien été supprimée. Les cadeaux sont déplacés dans la section \"Cadeaux sans section\"."
        );

        return $this->redirectToList($section);
    }

    #[Route('/users/{id}/sections', requirements: ['id' => '\d+'], name: 'app_section_user_page')]
    public function userPage(GiftRepository $giftRepository, SectionRepository $sectionRepository, CrewRepository $crewRepository, User $user, BreadcrumbBuilder $breadcrumbBuilder, Request $request): Response
    {
        if (!$this->isGranted('user.can_see_gifts', $user)) {
            // Neither sections nor gifts: what the template does not receive, it cannot show.
            return $this->render('section/user_page_locked.html.twig', [
                'user' => $user,
                'breadcrumbs' => $breadcrumbBuilder->giftFromUserSection($user),
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

        return $this->render('section/user_page.html.twig', [
            'giftsWithoutSection' => $giftsWithoutSection,
            'sections' => $sectionRepository->findPersonalOf($user),
            // Seeing the user's gifts, the viewer sees their crews' lists too: a member's
            // friends see the list.
            'crews' => $crewRepository->findForMemberWithGifts($user),
            'user' => $user,
            'breadcrumbs' => $breadcrumbBuilder->giftFromUserSection($user),
            'btnReturn' => $request->headers->get('referer'),
        ]);
    }

    /**
     * Back to the list the section belongs to: its crew's page, or its creator's own list.
     */
    private function redirectToList(Section $section): Response
    {
        $crew = $section->getCrew();

        return null !== $crew
            ? $this->redirectToRoute('app_crew_show', ['id' => $crew->getId()])
            : $this->redirectToRoute('app_section', ['id' => $section->getCreatedBy()->getId()])
        ;
    }
}
