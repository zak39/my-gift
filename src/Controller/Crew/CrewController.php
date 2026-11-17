<?php

namespace App\Controller\Crew;

use App\Entity\Crew;
use App\Entity\CrewMember;
use App\Entity\User;
use App\Form\CrewInvitationType;
use App\Form\CrewType;
use App\Services\BreadcrumbBuilder;
use App\Services\Lifecycle\CrewLifecycle;
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
 * A crew's page, and running the crew: its members have equal rights. The user's list of
 * crews and creating one are Me\CrewController's.
 */
#[IsGranted('IS_AUTHENTICATED')]
#[Route('/crews/{id}', requirements: ['id' => '\d+'])]
final class CrewController extends AbstractController
{
    /**
     * One page for everyone who may see the list: its members run it, the friends of a
     * member see it as they see a friend's personal list.
     */
    #[IsGranted('crew.can_see_list', subject: 'crew')]
    #[Route('', name: 'app_crew_show')]
    public function show(Crew $crew, BreadcrumbBuilder $breadcrumbBuilder): Response
    {
        if (!$this->isGranted('crew.is_member', $crew)) {
            return $this->render('crew/show.html.twig', [
                'crew' => $crew,
                'ownerView' => false,
            ]);
        }

        /** @var User $user */
        $user = $this->getUser();

        return $this->render('crew/show.html.twig', [
            'crew' => $crew,
            'ownerView' => true,
            'inviteForm' => $this->createForm(CrewInvitationType::class, null, [
                'crew' => $crew,
                'inviter' => $user,
                'action' => $this->generateUrl('app_crew_invite', ['id' => $crew->getId()]),
            ]),
            'breadcrumbs' => $breadcrumbBuilder->crew($crew),
        ]);
    }

    #[IsGranted('crew.is_member', subject: 'crew')]
    #[Route('/edit', name: 'app_crew_edit')]
    public function edit(Crew $crew, Request $request, EntityManagerInterface $entityManager, BreadcrumbBuilder $breadcrumbBuilder): Response
    {
        $form = $this->createForm(CrewType::class, $crew);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', "Le groupe « {$crew->getName()} » a bien été modifié.");

            return $this->redirectToRoute('app_crew_show', ['id' => $crew->getId()]);
        }

        if ($form->isSubmitted()) {
            // handleRequest() wrote the rejected name into $crew: reload the saved one for the
            // page. The form still shows what was typed, since it keeps its own submitted data.
            $entityManager->refresh($crew);
        }

        return $this->render('crew/edit.html.twig', [
            'form' => $form,
            'crew' => $crew,
            'breadcrumbs' => $breadcrumbBuilder->editCrew($crew),
        ]);
    }

    #[IsGranted('crew.can_leave', subject: 'crew')]
    #[IsCsrfTokenValid(new Expression('"crew-leave-" ~ args["crew"].getId()'))]
    #[Route('/leave', name: 'app_crew_leave', methods: ['POST'])]
    public function leave(Crew $crew, CrewLifecycle $crewLifecycle): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $crewLifecycle->leave($crew, $user);
        } catch (\LogicException $exception) {
            // Made owner in the meantime: CrewLifecycle reads the crew again before letting go.
            $this->addFlash('error', $exception->getMessage());

            return $this->redirectToRoute('app_crew_show', ['id' => $crew->getId()]);
        }

        $this->addFlash('success', "Vous avez quitté le groupe « {$crew->getName()} ».");

        return $this->redirectToRoute('app_crew');
    }

    /**
     * The new owner is looked for among the crew's members: anyone else is not found. A
     * member who is not, or no longer, its owner (a page left open) is told so, not refused.
     */
    #[IsGranted('crew.is_member', subject: 'crew')]
    #[IsCsrfTokenValid(new Expression('"crew-transfer-" ~ args["crew"].getId() ~ "-" ~ args["membership"].getUser().getId()'))]
    #[Route('/members/{member}/transfer', name: 'app_crew_transfer', requirements: ['member' => '\d+'], methods: ['POST'])]
    public function transfer(
        Crew $crew,
        #[MapEntity(mapping: ['id' => 'crew', 'member' => 'user'])] CrewMember $membership,
        CrewLifecycle $crewLifecycle,
    ): Response {
        if (!$this->isGranted('crew.can_transfer', $crew)) {
            $this->addFlash('error', 'Seule la personne propriétaire du groupe peut le confier à un autre membre.');

            return $this->redirectToRoute('app_crew_show', ['id' => $crew->getId()]);
        }

        $newOwner = $membership->getUser();

        try {
            $crewLifecycle->transfer($crew, $newOwner);
            $this->addFlash('success', "{$newOwner->getPseudo()} est maintenant propriétaire du groupe « {$crew->getName()} ». Vous en restez membre, et pouvez désormais le quitter.");
        } catch (\LogicException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('app_crew_show', ['id' => $crew->getId()]);
    }

    #[IsGranted('crew.can_delete', subject: 'crew')]
    #[IsCsrfTokenValid(new Expression('"crew-delete-" ~ args["crew"].getId()'))]
    #[Route('/delete', name: 'app_crew_delete', methods: ['POST'])]
    public function delete(Crew $crew, CrewLifecycle $crewLifecycle): Response
    {
        $crewLifecycle->delete($crew);

        $this->addFlash('success', "Le groupe « {$crew->getName()} » a bien été supprimé. Ses cadeaux sont retournés dans les « Cadeaux sans section » de celles et ceux qui les avaient ajoutés.");

        return $this->redirectToRoute('app_crew');
    }
}
