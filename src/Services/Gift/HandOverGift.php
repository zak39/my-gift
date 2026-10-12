<?php

namespace App\Services\Gift;

use App\Entity\Gift;
use App\Entity\User;
use Symfony\Component\Filesystem\Filesystem;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * Gives a gift another author, its image along: UserGiftDirectoryNamer files an image
 * under its gift's author.
 *
 * The image is copied, not moved, so that the call can sit in a transaction: rolled back,
 * the original is still in place and the copy is a harmless leftover. The original is for
 * the caller to delete, once its transaction is committed.
 *
 * No flush: the caller hands over several gifts at once and flushes them together.
 */
final class HandOverGift
{
    public function __construct(
        private readonly StorageInterface $storage,
        private readonly Filesystem $filesystem,
    ) {
    }

    public function __invoke(Gift $gift, User $newAuthor): void
    {
        $from = $gift->getImage() ? $this->storage->resolvePath($gift, 'imageFile') : null;
        $gift->setCreatedBy($newAuthor);

        // A file missing from disk is no reason to stop the handover.
        if (null !== $from && $this->filesystem->exists($from)) {
            // copy() creates the new author's folder when needed.
            $this->filesystem->copy($from, $this->storage->resolvePath($gift, 'imageFile'));
        }
    }
}
