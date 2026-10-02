<?php

namespace App\Controller;

use App\Entity\Crew;
use App\Entity\CrewInvitation;
use App\Entity\User;
use App\Form\CrewInvitationType;
use App\Form\CrewType;
use App\Repository\CrewInvitationRepository;
use App\Repository\CrewRepository;
use App\Repository\FriendBookRepository;
use App\Services\BreadcrumbBuilder;
use App\Services\Lifecycle\CrewLifecycle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED')]
final class CrewController extends AbstractController
{
    #[Route('/crews', name: 'app_crew')]
    public function index(CrewRepository $crewRepository, CrewInvitationRepository $crewInvitationRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('crew/index.html.twig', [
            'crews' => $crewRepository->findForMemberWithGifts($user),
            'invitations' => $crewInvitationRepository->findPendingReceivedBy($user),
        ]);
    }

    #[Route('/crews/new', name: 'app_crew_new')]
    public function new(Request $request, EntityManagerInterface $entityManager, BreadcrumbBuilder $breadcrumbBuilder): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $crew = new Crew($user);

        $form = $this->createForm(CrewType::class, $crew);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($crew);
            $entityManager->flush();

            $this->addFlash('success', "Le groupe « {$crew->getName()} » a bien été créé. Invitez vos ami·es à le rejoindre !");

            return $this->redirectToRoute('app_crew_show', ['id' => $crew->getId()]);
        }

        return $this->render('crew/new.html.twig', [
            'form' => $form,
            'breadcrumbs' => $breadcrumbBuilder->newCrew(),
        ]);
    }

    /**
     * One page for everyone who may see the list: its members run it, the friends of a
     * member see it as they see a friend's personal list.
     */
    #[IsGranted('crew.can_see_list', subject: 'crew')]
    #[Route('/crews/{id}', name: 'app_crew_show', requirements: ['id' => '\d+'])]
    public function show(Crew $crew, FriendBookRepository $friendBookRepository, BreadcrumbBuilder $breadcrumbBuilder): Response
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
            'inviteForm' => $this->createInviteForm($crew, $user, $friendBookRepository),
            'breadcrumbs' => $breadcrumbBuilder->crew($crew),
        ]);
    }

    #[IsGranted('crew.is_member', subject: 'crew')]
    #[Route('/crews/{id}/edit', name: 'app_crew_edit', requirements: ['id' => '\d+'])]
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

    #[IsGranted('crew.is_member', subject: 'crew')]
    #[Route('/crews/{id}/invite', name: 'app_crew_invite', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function invite(Crew $crew, Request $request, CrewLifecycle $crewLifecycle, FriendBookRepository $friendBookRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createInviteForm($crew, $user, $friendBookRepository);
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

    #[IsGranted('crew.can_leave', subject: 'crew')]
    #[Route('/crews/{id}/leave', name: 'app_crew_leave', requirements: ['id' => '\d+'], methods: ['POST'])]
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
    #[Route('/crews/{id}/delete', name: 'app_crew_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Crew $crew, Request $request, CrewLifecycle $crewLifecycle): Response
    {
        if (!$this->isCsrfTokenValid("crew-delete-{$crew->getId()}", $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $crewLifecycle->delete($crew);

        $this->addFlash('success', "Le groupe « {$crew->getName()} » a bien été supprimé. Ses cadeaux sont retournés dans les « Cadeaux sans section » de celles et ceux qui les avaient ajoutés.");

        return $this->redirectToRoute('app_crew');
    }

    /**
     * No voter on the route: the lifecycle checks can(), which runs the workflow guards,
     * which ask CrewInvitationVoter. A forged request ends in an error message.
     */
    #[Route('/crew-invitations/{id}/{transition}', name: 'app_crew_invitation', requirements: ['id' => '\d+', 'transition' => 'accept|refuse|cancel'], methods: ['POST'])]
    public function answer(CrewInvitation $invitation, string $transition, Request $request, CrewLifecycle $crewLifecycle): Response
    {
        if (!$this->isCsrfTokenValid("crew-invitation-$transition-{$invitation->getId()}", $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $crew = $invitation->getCrew();
        $inviteeName = $invitation->getInvitee()->getFirstname();

        try {
            match ($transition) {
                'accept' => $crewLifecycle->accept($invitation),
                'refuse' => $crewLifecycle->refuse($invitation),
                'cancel' => $crewLifecycle->cancel($invitation),
            };
        } catch (\LogicException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $this->redirectToRoute('app_crew');
        }

        $this->addFlash('success', match ($transition) {
            'accept' => "Bienvenue dans le groupe « {$crew->getName()} » !",
            'refuse' => "L'invitation au groupe « {$crew->getName()} » a été refusée.",
            'cancel' => "L'invitation de $inviteeName a été annulée.",
        });

        // Refusing stays among the invitations; accepting or cancelling leads to the crew.
        return 'refuse' === $transition
            ? $this->redirectToRoute('app_crew')
            : $this->redirectToRoute('app_crew_show', ['id' => $crew->getId()])
        ;
    }

    /**
     * The user's friends who are neither in the crew nor invited yet: the form rejects
     * anyone else.
     */
    private function createInviteForm(Crew $crew, User $user, FriendBookRepository $friendBookRepository): FormInterface
    {
        $candidates = $friendBookRepository->of($user)->getFriends()->filter(
            fn (User $friend) => !$crew->hasMember($friend) && null === $crew->getInvitationFor($friend),
        );

        return $this->createForm(CrewInvitationType::class, null, [
            'candidates' => $candidates->getValues(),
            'action' => $this->generateUrl('app_crew_invite', ['id' => $crew->getId()]),
        ]);
    }
}
