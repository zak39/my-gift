<?php

namespace App\Controller\Crew;

use App\Entity\Crew;
use App\Entity\User;
use App\Form\CrewInvitationType;
use App\Form\CrewType;
use App\Services\BreadcrumbBuilder;
use App\Services\Lifecycle\CrewLifecycle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
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
    #[Route('/leave', name: 'app_crew_leave', methods: ['POST'])]
    public function leave(Crew $crew, Request $request, CrewLifecycle $crewLifecycle): Response
    {
        if (!$this->isCsrfTokenValid("crew-leave-{$crew->getId()}", $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();

        $crewLifecycle->leave($crew, $user);

        $this->addFlash('success', "Vous avez quitté le groupe « {$crew->getName()} ».");

        return $this->redirectToRoute('app_crew');
    }

    #[IsGranted('crew.can_delete', subject: 'crew')]
    #[Route('/delete', name: 'app_crew_delete', methods: ['POST'])]
    public function delete(Crew $crew, Request $request, CrewLifecycle $crewLifecycle): Response
    {
        if (!$this->isCsrfTokenValid("crew-delete-{$crew->getId()}", $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $crewLifecycle->delete($crew);

        $this->addFlash('success', "Le groupe « {$crew->getName()} » a bien été supprimé. Ses cadeaux sont retournés dans les « Cadeaux sans section » de celles et ceux qui les avaient ajoutés.");

        return $this->redirectToRoute('app_crew');
    }
}
