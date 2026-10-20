<?php

namespace App\Controller\Me;

use App\Entity\Gift;
use App\Entity\User;
use App\Form\GiftEditType;
use App\Form\GiftType;
use App\Repository\SectionRepository;
use App\Services\BreadcrumbBuilder;
use App\Services\Gift\CreateGift;
use App\Services\Gift\DeleteGift;
use App\Services\Gift\UpdateGift;
use App\Services\RefererRedirector;
use App\Services\Url\GiftOwnerUrlGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The gifts of the user's own list. Those of a crew's list are run from
 * Crew\GiftController; the actions in Services\Gift (CreateGift, UpdateGift, DeleteGift)
 * hold what both do alike.
 */
#[IsGranted('IS_AUTHENTICATED')]
#[Route('/me/gifts')]
final class GiftController extends AbstractController
{
    #[Route('/new', name: 'app_gift_create')]
    public function create(Request $request, CreateGift $createGift, SectionRepository $sectionRepository, BreadcrumbBuilder $breadcrumbBuilder): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $gift = new Gift();
        // Its author before the form validates it: out of a crew, a gift needs one (Gift::$createdBy).
        $gift->setCreatedBy($user);

        // "Ajouter un cadeau" on a section files the gift straight into it. The submitted
        // form posts back to the same URL, so the section is set again before saving.
        // An optional hint, not a nested resource of the URL: a section that is not one of
        // the user's own is ignored (the gift is created unfiled) rather than a 404.
        $section = $sectionRepository->find($request->query->getInt('section'));
        if (null !== $section && null === $section->getCrew() && $this->isGranted('section.is_owner', $section)) {
            $gift->setSection($section);
        }

        $form = $this->createForm(GiftType::class, $gift);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $createGift($gift, $user, $form->get('openGraphImageUrl')->getData());

            return $this->redirectToRoute('app_section');
        }

        return $this->render('gift/create.html.twig', [
            'form' => $form,
            'gift' => $gift,
            'cancelUrl' => $this->generateUrl('app_section'),
            'breadcrumbs' => $breadcrumbBuilder->newGift(),
        ]);
    }

    #[IsGranted('gift.is_owner', subject: 'gift', statusCode: 404)]
    #[Route('/{id}/show', name: 'app_me_gift_show', requirements: ['id' => '\d+'])]
    public function show(#[MapEntity(expr: 'repository.findOnePersonal(id)')] Gift $gift, BreadcrumbBuilder $breadcrumbBuilder, GiftOwnerUrlGenerator $giftOwnerUrlGenerator, RefererRedirector $refererRedirector, Request $request): Response
    {
        return $this->render('gift/show.html.twig', [
            'gift' => $gift,
            'breadcrumbs' => $breadcrumbBuilder->myGift($gift),
            // Opened from an email, with no referer: back to the owner's list, not the public one.
            'btnReturn' => $refererRedirector->localReferer($request) ?? $giftOwnerUrlGenerator->list($gift),
        ]);
    }

    #[IsGranted('gift.is_owner', subject: 'gift', statusCode: 404)]
    #[Route('/{id}/edit', name: 'app_gift_edit', requirements: ['id' => '\d+'])]
    public function edit(#[MapEntity(expr: 'repository.findOnePersonal(id)')] Gift $gift, Request $request, UpdateGift $updateGift, EntityManagerInterface $entityManager, GiftOwnerUrlGenerator $giftOwnerUrlGenerator, BreadcrumbBuilder $breadcrumbBuilder): Response
    {
        // A gift of the user's own list is always theirs: its author edits it.
        $form = $this->createForm(GiftEditType::class, $gift);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $updateGift($gift);

            // Filed in a crew's section, the gift now lives in the crew's list.
            return $this->redirect($giftOwnerUrlGenerator->list($gift));
        }

        if ($form->isSubmitted()) {
            // handleRequest() wrote the rejected values into $gift: reload the saved ones for
            // the page. The form still shows what was typed, as it keeps its own submitted data.
            $entityManager->refresh($gift);
        }

        return $this->render('gift/edit.html.twig', [
            'form' => $form,
            'gift' => $gift,
            'breadcrumbs' => $breadcrumbBuilder->editGift($gift),
        ]);
    }

    #[IsGranted('gift.is_owner', subject: 'gift', statusCode: 404)]
    #[Route('/{id}/delete', name: 'app_gift_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(#[MapEntity(expr: 'repository.findOnePersonal(id)')] Gift $gift, Request $request, DeleteGift $deleteGift): Response
    {
        if (!$this->isCsrfTokenValid("gift-delete-{$gift->getId()}", $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $deleteGift($gift);

        $this->addFlash('success', "Le cadeau \"{$gift->getTitle()}\" a bien été supprimé.");

        return $this->redirectToRoute('app_section');
    }
}
