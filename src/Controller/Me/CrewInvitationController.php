<?php

namespace App\Controller\Me;

use App\Entity\CrewInvitation;
use App\Services\Lifecycle\CrewLifecycle;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Answering an invitation the user received. Inviting someone, and cancelling an
 * invitation, are the crew's: Crew\InvitationController.
 */
#[IsGranted('IS_AUTHENTICATED')]
#[Route('/me/crew-invitations')]
final class CrewInvitationController extends AbstractController
{
    /**
     * No voter on the route: the lifecycle checks can(), which runs the workflow guards,
     * which ask CrewInvitationVoter. A forged request ends in an error message.
     */
    #[Route('/{id}/{transition}', name: 'app_crew_invitation', requirements: ['id' => '\d+', 'transition' => 'accept|refuse'], methods: ['POST'])]
    public function answer(CrewInvitation $invitation, string $transition, Request $request, CrewLifecycle $crewLifecycle): Response
    {
        if (!$this->isCsrfTokenValid("crew-invitation-$transition-{$invitation->getId()}", $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $crew = $invitation->getCrew();

        try {
            match ($transition) {
                'accept' => $crewLifecycle->accept($invitation),
                'refuse' => $crewLifecycle->refuse($invitation),
            };
        } catch (\LogicException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $this->redirectToRoute('app_crew');
        }

        // Refusing stays among the invitations; accepting leads to the crew.
        if ('refuse' === $transition) {
            $this->addFlash('success', "L'invitation au groupe « {$crew->getName()} » a été refusée.");

            return $this->redirectToRoute('app_crew');
        }

        $this->addFlash('success', "Bienvenue dans le groupe « {$crew->getName()} » !");

        return $this->redirectToRoute('app_crew_show', ['id' => $crew->getId()]);
    }
}
