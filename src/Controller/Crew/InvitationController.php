<?php

namespace App\Controller\Crew;

use App\Entity\Crew;
use App\Entity\CrewInvitation;
use App\Entity\User;
use App\Form\CrewInvitationType;
use App\Services\Lifecycle\CrewLifecycle;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * A member inviting a friend into the crew, or cancelling an invitation sent. Answering
 * an invitation received is Me\CrewInvitationController's.
 */
#[IsGranted('IS_AUTHENTICATED')]
#[Route('/crews/{id}', requirements: ['id' => '\d+'])]
final class InvitationController extends AbstractController
{
    #[IsGranted('crew.is_member', subject: 'crew')]
    #[Route('/invite', name: 'app_crew_invite', methods: ['POST'])]
    public function invite(Crew $crew, Request $request, CrewLifecycle $crewLifecycle): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // The same options as on the crew's page, so the same people are accepted.
        $form = $this->createForm(CrewInvitationType::class, null, [
            'crew' => $crew,
            'inviter' => $user,
        ]);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash('error', 'Votre invitation n\'a pas pu être envoyée. Choisissez une personne dans la liste.');

            return $this->redirectToRoute('app_crew_show', ['id' => $crew->getId()]);
        }

        /** @var User $invitee */
        $invitee = $form->get('invitee')->getData();

        try {
            $crewLifecycle->invite($crew, $user, $invitee);
            $this->addFlash('success', "Votre invitation a été envoyée à {$invitee->getFirstname()}.");
        } catch (\LogicException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('app_crew_show', ['id' => $crew->getId()]);
    }

    /**
     * No voter on the route: the lifecycle checks can(), which runs the workflow guards,
     * which ask CrewInvitationVoter. A forged request ends in an error message.
     */
    #[Route('/invitations/{invitation}/cancel', name: 'app_crew_invitation_cancel', requirements: ['invitation' => '\d+'], methods: ['POST'])]
    public function cancel(
        Crew $crew,
        #[MapEntity(expr: 'repository.findOneBy({"id": invitation, "crew": id})')] CrewInvitation $invitation,
        Request $request,
        CrewLifecycle $crewLifecycle,
    ): Response {
        if (!$this->isCsrfTokenValid("crew-invitation-cancel-{$invitation->getId()}", $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $inviteeName = $invitation->getInvitee()->getFirstname();

        try {
            $crewLifecycle->cancel($invitation);
        } catch (\LogicException $exception) {
            $this->addFlash('error', $exception->getMessage());

            // The user may no longer see the crew's page (they left it): back to their crews.
            return $this->redirectToRoute('app_crew');
        }

        $this->addFlash('success', "L'invitation de $inviteeName a été annulée.");

        return $this->redirectToRoute('app_crew_show', ['id' => $crew->getId()]);
    }
}
