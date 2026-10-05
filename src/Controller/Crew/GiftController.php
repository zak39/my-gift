<?php

namespace App\Controller\Crew;

use App\Entity\Crew;
use App\Entity\Gift;
use App\Entity\User;
use App\Form\GiftCrewMemberEditType;
use App\Form\GiftEditType;
use App\Form\GiftType;
use App\Repository\SectionRepository;
use App\Services\BreadcrumbBuilder;
use App\Services\Gift\CreateGift;
use App\Services\Gift\DeleteGift;
use App\Services\Gift\UpdateGift;
use App\Services\Url\GiftOwnerUrlGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The gifts of a crew's list, run by any of its members. The URL names the crew: a gift
 * of another list is not found here, whatever its id. The actions in Services\Gift hold what
 * this and Me\GiftController do alike.
 */
#[IsGranted('IS_AUTHENTICATED')]
#[IsGranted('crew.is_member', subject: 'crew')]
#[Route('/crews/{id}/gifts', requirements: ['id' => '\d+'])]
final class GiftController extends AbstractController
{
    /**
     * A crew's gift is always filed: the "Ajouter un cadeau" of a section passes it.
     */
    #[Route('/new', name: 'app_crew_gift_create')]
    public function create(Crew $crew, Request $request, CreateGift $createGift, SectionRepository $sectionRepository, BreadcrumbBuilder $breadcrumbBuilder): Response
    {
        $section = $sectionRepository->find($request->query->getInt('section'));
        if (null === $section || $section->getCrew() !== $crew) {
            throw $this->createNotFoundException();
        }

        /** @var User $user */
        $user = $this->getUser();
        $gift = (new Gift())->setSection($section);

        $form = $this->createForm(GiftType::class, $gift);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $createGift($gift, $user, $form->get('openGraphImageUrl')->getData());

            return $this->redirectToRoute('app_crew_show', ['id' => $crew->getId()]);
        }

        return $this->render('gift/create.html.twig', [
            'form' => $form,
            'gift' => $gift,
            'cancelUrl' => $this->generateUrl('app_crew_show', ['id' => $crew->getId()]),
            'breadcrumbs' => $breadcrumbBuilder->newCrewGift($crew),
        ]);
    }

    #[Route('/{gift}/show', name: 'app_crew_gift_show', requirements: ['gift' => '\d+'])]
    public function show(Crew $crew, #[MapEntity(id: 'gift')] Gift $gift, BreadcrumbBuilder $breadcrumbBuilder, Request $request): Response
    {
        if ($gift->getCrew() !== $crew) {
            throw $this->createNotFoundException();
        }

        return $this->render('gift/show.html.twig', [
            'gift' => $gift,
            'breadcrumbs' => $breadcrumbBuilder->crewGift($gift),
            'btnReturn' => $request->headers->get('referer'),
        ]);
    }

    #[Route('/{gift}/edit', name: 'app_crew_gift_edit', requirements: ['gift' => '\d+'])]
    public function edit(Crew $crew, #[MapEntity(id: 'gift')] Gift $gift, Request $request, UpdateGift $updateGift, EntityManagerInterface $entityManager, GiftOwnerUrlGenerator $giftOwnerUrlGenerator, BreadcrumbBuilder $breadcrumbBuilder): Response
    {
        if ($gift->getCrew() !== $crew) {
            throw $this->createNotFoundException();
        }

        /** @var User $user */
        $user = $this->getUser();

        // Read before handleRequest(), which writes the submitted price into $gift.
        $previousPrice = $gift->getPrice();

        // Its author may move it out of the crew's list; another member only within it.
        $formType = $gift->getCreatedBy() === $user ? GiftEditType::class : GiftCrewMemberEditType::class;
        $form = $this->createForm($formType, $gift);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $updateGift($gift, $previousPrice);

            // Its author may have moved it to their own list, or to another crew's.
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
            'breadcrumbs' => $breadcrumbBuilder->editCrewGift($gift),
        ]);
    }

    #[Route('/{gift}/delete', name: 'app_crew_gift_delete', requirements: ['gift' => '\d+'], methods: ['DELETE'])]
    public function delete(Crew $crew, #[MapEntity(id: 'gift')] Gift $gift, DeleteGift $deleteGift): Response
    {
        if ($gift->getCrew() !== $crew) {
            throw $this->createNotFoundException();
        }

        $deleteGift($gift);

        $this->addFlash('success', "Le cadeau \"{$gift->getTitle()}\" a bien été supprimé.");

        return $this->redirectToRoute('app_crew_show', ['id' => $crew->getId()]);
    }
}
