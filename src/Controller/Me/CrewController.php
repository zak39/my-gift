<?php

namespace App\Controller\Me;

use App\Entity\Crew;
use App\Entity\User;
use App\Form\CrewType;
use App\Repository\CrewInvitationRepository;
use App\Repository\CrewRepository;
use App\Services\BreadcrumbBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The crews the user belongs to, the invitations they received, and creating one. A
 * crew's own page and its running are Crew\CrewController's.
 */
#[IsGranted('IS_AUTHENTICATED')]
#[Route('/me/crews')]
final class CrewController extends AbstractController
{
    #[Route('', name: 'app_crew')]
    public function index(CrewRepository $crewRepository, CrewInvitationRepository $crewInvitationRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('me/crew/index.html.twig', [
            'crews' => $crewRepository->findForMemberWithGifts($user),
            'invitations' => $crewInvitationRepository->findPendingReceivedBy($user),
        ]);
    }

    #[Route('/new', name: 'app_crew_new')]
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

        return $this->render('me/crew/new.html.twig', [
            'form' => $form,
            'breadcrumbs' => $breadcrumbBuilder->newCrew(),
        ]);
    }
}
