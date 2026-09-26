<?php

namespace App\Controller;

use App\Entity\Gift;
use App\Enum\GiftStatus;
use App\Form\GiftEditType;
use App\Form\GiftType;
use App\Repository\GiftRepository;
use App\Services\BreadcrumbBuilder;
use App\Services\FavoriteGiftGrouper;
use App\Services\GiftLifecycle;
use App\Services\ImageDownloader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
    public function create(Request $request, EntityManagerInterface $entityManager, ImageDownloader $imageDownloader, BreadcrumbBuilder $breadcrumbBuilder): Response
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
            'breadcrumbs' => $breadcrumbBuilder->newGift(),
        ]);
    }

    #[Route('/gift/{id}/edit', name: 'app_gift_edit')]
    public function edit(Request $request, Gift $gift, EntityManagerInterface $entityManager, BreadcrumbBuilder $breadcrumbBuilder, GiftLifecycle $giftLifecycle): Response
    {
        $this->denyAccessUnlessGranted('gift.is_creator', $gift);

        /** @var App\Entity\User */
        $user = $this->getUser();

        // Read before handleRequest(), which writes the submitted price into $gift.
        $previousPrice = $gift->getPrice();

        $form = $this->createForm(GiftEditType::class, $gift, ['sections' => $user->getSections()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($gift);
            $entityManager->flush();

            if ($previousPrice !== $gift->getPrice()) {
                $giftLifecycle->priceChanged($gift, $previousPrice);
            }

            return $this->redirectToRoute('app_section', ['id' => $user->getId()]);
        }

        if ($form->isSubmitted()) {
            // handleRequest() wrote the rejected values into $gift: reload the saved ones for the
            // page. The form still shows what was typed, since it keeps its own submitted data.
            $entityManager->refresh($gift);
        }

        return $this->render('gift/edit.html.twig', [
            'form' => $form,
            'gift' => $gift,
            'breadcrumbs' => $breadcrumbBuilder->editGift($gift),
        ]);
    }

    #[IsGranted('gift.can_see', subject: 'gift')]
    #[Route('/gift/{id}/show', name: 'app_gift_show')]
    public function show(Gift $gift, BreadcrumbBuilder $breadcrumbBuilder, Request $request): Response
    {
        $user = $this->getUser();

        $isOwnGift = $gift->getCreatedBy() === $user;
        $breadcrumbs = $isOwnGift
            ? $breadcrumbBuilder->giftFromMySections($gift)
            : $breadcrumbBuilder->giftFromUserPage($gift)
        ;

        return $this->render('gift/show.html.twig', [
            'gift' => $gift,
            'breadcrumbs' => $breadcrumbs,
            'btnReturn' => $request->headers->get('referer'),
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

    #[IsGranted('gift.can_see', subject: 'gift')]
    #[Route('/gift/{id}/favorite', name: 'app_gift_add_favorite', methods: ['POST'])]
    public function addFavorite(Gift $gift, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User */
        $user = $this->getUser();

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
    public function getFavoriteGiftsBy(FavoriteGiftGrouper $favoriteGiftGrouper, GiftRepository $giftRepository): Response
    {
        /** @var \App\Entity\User */
        $user = $this->getUser();

        $gifts = $giftRepository->findFavoritesGifts($user);

        return $this->render('gift/favorites.html.twig', [
            'giftCount' => \count($gifts),
            'favoriteGiftsByUsers' => $favoriteGiftGrouper->groupedByCreator($gifts),
        ]);
    }
}
