<?php

namespace App\Services\Gift;

use App\Entity\Gift;
use App\Entity\User;
use App\Enum\GiftStatus;
use App\Services\ImageDownloader;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Saves a gift its author just filled in, in their own list (Me\GiftController) or in a
 * crew's (Crew\GiftController).
 */
final class CreateGift
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ImageDownloader $imageDownloader,
    ) {
    }

    /**
     * @param string|null $openGraphImageUrl the product's image, read from its page, used
     *                                       when the author uploaded none
     */
    public function __invoke(Gift $gift, User $author, ?string $openGraphImageUrl): void
    {
        if (!$gift->getImageFile() && $openGraphImageUrl) {
            $imageFile = $this->imageDownloader->download($openGraphImageUrl);
            if ($imageFile) {
                $gift->setImageFile($imageFile);
            }
        }

        $gift
            ->setCreatedAt(new \DateTimeImmutable())
            ->setCreatedBy($author)
            ->setStatus(GiftStatus::CREATED)
            ->setSlug(strtolower(str_replace(' ', '-', $gift->getTitle())))
        ;

        $this->entityManager->persist($gift);
        $this->entityManager->flush();
    }
}
