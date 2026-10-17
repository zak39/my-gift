<?php

namespace App\Services\User;

use App\Entity\User;
use App\Services\DicebearAvatarGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Vich\UploaderBundle\Handler\UploadHandler;

/**
 * Takes a user back to their DiceBear avatar: the photo they uploaded is deleted, and a
 * DiceBear avatar is downloaded when they have none. Offered by
 * Molecule:User:ResetAvatarButton when there is something to reset.
 *
 * The download runs in the request, unlike at registration (GenerateDefaultAvatarHandler): the
 * user clicked and waits for its outcome, told by a flash.
 */
final class ResetAvatar
{
    public function __construct(
        private readonly UploadHandler $uploadHandler,
        private readonly DicebearAvatarGenerator $avatarGenerator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return bool whether the user ends up with a DiceBear avatar: false when DiceBear could
     *              not provide one, the pages then showing the placeholder icon
     */
    public function __invoke(User $user): bool
    {
        $this->uploadHandler->remove($user, 'avatarFile');
        // remove() has already deleted the file: recorded at once, before DiceBear and its
        // upload, which may fail and would leave `avatar` naming a file that is gone
        $this->entityManager->flush();

        if (null === $user->getAvatarDefault()) {
            $uploadedFile = $this->avatarGenerator->generate($user->getFirstname());
            $user->setAvatarDefaultFile($uploadedFile);
        }

        $this->entityManager->flush();

        return null !== $user->getAvatarDefault();
    }
}
