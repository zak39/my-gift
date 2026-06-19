<?php

namespace App\Controller;

use App\Entity\Gift;
use App\Enum\GiftStatus;
use App\Form\GiftEditType;
use App\Form\GiftType;
use App\Repository\GiftRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GiftController extends AbstractController
{
    #[Route('/gift', name: 'app_gift')]
    public function index(GiftRepository $repository): Response
    {
        $gifts = $repository->findAll();

        return $this->render('gift/index.html.twig', [
            'gifts' => $gifts,
        ]);
    }

    #[Route('/gift/create', name: 'app_gift_create')]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $gift = new Gift();

        $form = $this->createForm(GiftType::class, $gift);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $gift->setCreatedAt(new \DateTimeImmutable());
            $gift->setCreatedBy($this->getUser());
            $gift->setStatus(GiftStatus::CREATED);

            $title = $gift->getTitle();
            $slug = str_replace(' ', '-', $title);
            $slug = strtolower($slug);
            $gift->setSlug($slug);

            $entityManager->persist($gift);
            $entityManager->flush();

            return $this->redirectToRoute('app_gift');
        }

        return $this->render('gift/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/gift/{id}/edit', name: 'app_gift_edit')]
    public function edit(Request $request, Gift $gift, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(GiftEditType::class, $gift);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($gift);
            $entityManager->flush();

            return $this->redirectToRoute('app_section');
        }

        return $this->render('gift/edit.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/gift/{id}/delete', name: 'app_gift_delete', methods: ['DELETE'])]
    public function delete(Gift $gift, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($gift);
        $entityManager->flush();

        $this->addFlash('success', "Le cadeau \"{$gift->getTitle()}\" a bien été supprimé.");

        return $this->redirectToRoute('app_section');
    }
}
