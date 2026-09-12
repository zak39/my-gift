<?php

namespace App\Controller;

use App\Entity\Gift;
use App\Enum\GiftStatus;
use App\Form\GiftEditType;
use App\Form\GiftType;
use App\Services\FavoriteGiftGrouper;
use App\Services\ImageDownloader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

#[IsGranted('IS_AUTHENTICATED')]
final class GiftController extends AbstractController
{
    #[Route('/gift', name: 'app_gift')]
    public function index(): Response
    {
        /** @var App\Entity\User */
        $user = $this->getUser();

        return $this->render('gift/index.html.twig', [
            'gifts' => $user->getGifts(),
        ]);
    }

    #[Route('/gift/create', name: 'app_gift_create')]
    public function create(Request $request, EntityManagerInterface $entityManager, ImageDownloader $imageDownloader): Response
    {
        $gift = new Gift();

        $form = $this->createForm(GiftType::class, $gift);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // If we retrieved the image from OpenGraph
            if (!$gift->getImageFile()) {
                $openGraphImageUrl = $form->get('openGraphImageUrl')->getData();
                if ($openGraphImageUrl) {
                    $imageFile = $imageDownloader->download($openGraphImageUrl);
                    if ($imageFile) {
                        $gift->setImageFile($imageFile);
                    }
                }
            }

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
            'gift' => $gift,
        ]);
    }

    #[Route('/gift/{id}/edit', name: 'app_gift_edit')]
    public function edit(Request $request, Gift $gift, EntityManagerInterface $entityManager, UploaderHelper $helper): Response
    {
        $this->denyAccessUnlessGranted('gift.is_creator', $gift);

        /** @var App\Entity\User */
        $user = $this->getUser();

        $form = $this->createForm(GiftEditType::class, $gift, ['sections' => $user->getSections()]);
        $form->handleRequest($request);

        $imagePath = $helper->asset($gift, 'imageFile');

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($gift);
            $entityManager->flush();

            return $this->redirectToRoute('app_section', ['id' => $user->getId()]);
        }

        return $this->render('gift/edit.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/gift/{id}/show', name: 'app_gift_show')]
    public function show(Gift $gift): Response
    {
        return $this->render('gift/show.html.twig', [
            'gift' => $gift,
        ]);
    }

    #[Route('/gift/{id}/delete', name: 'app_gift_delete', methods: ['DELETE'])]
    public function delete(Gift $gift, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('gift.is_creator', $gift);

        $entityManager->remove($gift);
        $entityManager->flush();

        $this->addFlash('success', "Le cadeau \"{$gift->getTitle()}\" a bien été supprimé.");

        /** @var \App\Entity\User */
        $user = $this->getUser();

        return $this->redirectToRoute('app_section', ['id' => $user->getId()]);
    }

    #[Route('/gift/{id}/favorite', name: 'app_gift_add_favorite', methods: ['POST'])]
    public function addFavorite(Gift $gift, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User */
        $user = $this->getUser();

        // dd($user);

        $user->addFavoriteGift($gift);
        $entityManager->flush();

        return new JsonResponse([
            'message' => sprintf('Le cadeau %s a été ajouté à vos favoris.', $gift->getTitle()),
        ]);
    }

    #[Route('/gift/{id}/favorite/remove', name: 'app_gift_remove_favorite', methods: ['POST'])]
    public function removeFavorite(Gift $gift, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User */
        $user = $this->getUser();

        $user->removeFavoriteGift($gift);
        $entityManager->flush();

        return new JsonResponse([
            'message' => sprintf('Le cadeau %s a été retiré à vos favoris.', $gift->getTitle()),
        ]);
    }

    #[Route('/user/favorites', name: 'app_gift_user_favorites', methods: ['GET'])]
    public function getFavoriteGiftsBy(FavoriteGiftGrouper $favoriteGiftGrouper): Response
    {
        /** @var \App\Entity\User */
        $user = $this->getUser();

        return $this->render('gift/favorites.html.twig', [
            'user' => $user,
            'favoriteGiftsByUsers' => $favoriteGiftGrouper->groupedByCreator($user),
        ]);
    }
}
