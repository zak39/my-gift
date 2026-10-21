<?php

namespace App\Controller\Crew;

use App\Entity\Crew;
use App\Entity\Gift;
use App\Entity\Section;
use App\Entity\User;
use App\Form\GiftCrewMemberEditType;
use App\Form\GiftEditType;
use App\Form\GiftType;
use App\Services\BreadcrumbBuilder;
use App\Services\Gift\ClaimGift;
use App\Services\Gift\CreateGift;
use App\Services\Gift\DeleteGift;
use App\Services\Gift\UpdateGift;
use App\Services\RefererRedirector;
use App\Services\Url\GiftOwnerUrlGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The gifts of a crew's list, run by any of its members. The URL names the crew, and a
 * gift is loaded through it (GiftRepository::findOneInCrew()): one of another list is not
 * found here, whatever its id. The actions in Services\Gift hold what
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
    public function create(
        Crew $crew,
        #[MapEntity(expr: 'repository.findOneBy({"id": request.query.getInt("section"), "crew": id})')] Section $section,
        Request $request,
        CreateGift $createGift,
        BreadcrumbBuilder $breadcrumbBuilder,
    ): Response {
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
    public function show(Crew $crew, #[MapEntity(expr: 'repository.findOneInCrew(gift, id)')] Gift $gift, BreadcrumbBuilder $breadcrumbBuilder, GiftOwnerUrlGenerator $giftOwnerUrlGenerator, RefererRedirector $refererRedirector, Request $request): Response
    {
        return $this->render('gift/show.html.twig', [
            'gift' => $gift,
            'breadcrumbs' => $breadcrumbBuilder->crewGift($gift),
            // Opened from an email, with no referer: back to the crew's list.
            'btnReturn' => $refererRedirector->localReferer($request) ?? $giftOwnerUrlGenerator->list($gift),
        ]);
    }

    #[Route('/{gift}/edit', name: 'app_crew_gift_edit', requirements: ['gift' => '\d+'])]
    public function edit(Crew $crew, #[MapEntity(expr: 'repository.findOneInCrew(gift, id)')] Gift $gift, Request $request, UpdateGift $updateGift, EntityManagerInterface $entityManager, GiftOwnerUrlGenerator $giftOwnerUrlGenerator, BreadcrumbBuilder $breadcrumbBuilder): Response
    {
        // $crew is unused here, yet required: it is the subject of the class's
        // #[IsGranted('crew.is_member', subject: 'crew')], found by its argument name.

        /** @var User $user */
        $user = $this->getUser();

        // Its author may move it out of the crew's list; another member only within it.
        $formType = $gift->getCreatedBy() === $user ? GiftEditType::class : GiftCrewMemberEditType::class;
        $form = $this->createForm($formType, $gift);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $updateGift($gift);

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

    /**
     * A member taking over a gift whose author deleted their account. No gift.can_claim
     * on the route: ClaimGift asks it under its lock, and a refusal (a page left open) is
     * a flash rather than a 403.
     */
    #[Route('/{gift}/claim', name: 'app_crew_gift_claim', requirements: ['gift' => '\d+'], methods: ['POST'])]
    public function claim(Crew $crew, #[MapEntity(expr: 'repository.findOneInCrew(gift, id)')] Gift $gift, Request $request, ClaimGift $claimGift, GiftOwnerUrlGenerator $giftOwnerUrlGenerator): Response
    {
        if (!$this->isCsrfTokenValid("gift-claim-{$gift->getId()}", $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();

        try {
            $claimGift($gift, $user);
        } catch (EntityNotFoundException) {
            $this->addFlash('error', 'Ce cadeau n\'existe plus : sa section ou son groupe vient d\'être supprimé.');

            // The user's crews: its crew may be gone with it.
            return $this->redirectToRoute('app_crew');
        } catch (\LogicException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $this->redirect($giftOwnerUrlGenerator->list($gift));
        }

        $this->addFlash('success', "Le cadeau « {$gift->getTitle()} » est maintenant le vôtre : si sa section ou son groupe est supprimé, il reviendra dans vos « Cadeaux sans section ».");

        return $this->redirect($giftOwnerUrlGenerator->show($gift));
    }

    #[Route('/{gift}/delete', name: 'app_crew_gift_delete', requirements: ['gift' => '\d+'], methods: ['DELETE'])]
    public function delete(Crew $crew, #[MapEntity(expr: 'repository.findOneInCrew(gift, id)')] Gift $gift, Request $request, DeleteGift $deleteGift): Response
    {
        if (!$this->isCsrfTokenValid("gift-delete-{$gift->getId()}", $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $deleteGift($gift);

        $this->addFlash('success', "Le cadeau \"{$gift->getTitle()}\" a bien été supprimé.");

        return $this->redirectToRoute('app_crew_show', ['id' => $crew->getId()]);
    }
}
