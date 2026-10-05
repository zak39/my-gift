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
use App\Services\Url\GiftOwnerUrlGenerator;
use Doctrine\ORM\EntityManagerInterface;
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

        // "Ajouter un cadeau" on a section files the gift straight into it. The submitted
        // form posts back to the same URL, so the section is set again before saving.
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

    #[IsGranted('gift.is_owner', subject: 'gift')]
    #[Route('/{id}/show', name: 'app_me_gift_show', requirements: ['id' => '\d+'])]
    public function show(Gift $gift, BreadcrumbBuilder $breadcrumbBuilder, Request $request): Response
    {
        // A crew's gift is shown from its crew's URL, as the links lead there.
        if (null !== $gift->getCrew()) {
            throw $this->createNotFoundException();
        }

        return $this->render('gift/show.html.twig', [
            'gift' => $gift,
            'breadcrumbs' => $breadcrumbBuilder->myGift($gift),
            'btnReturn' => $request->headers->get('referer'),
        ]);
    }

    #[IsGranted('gift.is_owner', subject: 'gift')]
    #[Route('/{id}/edit', name: 'app_gift_edit', requirements: ['id' => '\d+'])]
    public function edit(Gift $gift, Request $request, UpdateGift $updateGift, EntityManagerInterface $entityManager, GiftOwnerUrlGenerator $giftOwnerUrlGenerator, BreadcrumbBuilder $breadcrumbBuilder): Response
    {
        // A crew's gift is run from its crew's URL, as the links lead there.
        if (null !== $gift->getCrew()) {
            throw $this->createNotFoundException();
        }

        // Read before handleRequest(), which writes the submitted price into $gift.
        $previousPrice = $gift->getPrice();

        // A gift of the user's own list is always theirs: its author edits it.
        $form = $this->createForm(GiftEditType::class, $gift);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $updateGift($gift, $previousPrice);

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

    #[IsGranted('gift.is_owner', subject: 'gift')]
    #[Route('/{id}/delete', name: 'app_gift_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(Gift $gift, DeleteGift $deleteGift): Response
    {
        if (null !== $gift->getCrew()) {
            throw $this->createNotFoundException();
        }

        $deleteGift($gift);

        $this->addFlash('success', "Le cadeau \"{$gift->getTitle()}\" a bien été supprimé.");

        return $this->redirectToRoute('app_section');
    }
}
