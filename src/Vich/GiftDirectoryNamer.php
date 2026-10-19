<?php

namespace App\Vich;

use App\Entity\Crew;
use App\Entity\User;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\DirectoryNamerInterface;

/**
 * Where a gift's image lives: with the list the gift belongs to. A crew gift belongs to
 * the crew, whoever added it, so its image is filed under the crew, and an account
 * deletion leaves it in place. Vich asks again on every read: when the gift enters or
 * leaves a crew, MoveGiftImageListener moves the file.
 */
final class GiftDirectoryNamer implements DirectoryNamerInterface
{
    public function directoryName($object, PropertyMapping $mapping): string
    {
        return $this->directoryFor($object->getCrew(), $object->getCreatedBy());
    }

    /**
     * Also given a gift's previous crew and author, to find where its image was.
     */
    public function directoryFor(?Crew $crew, ?User $author): string
    {
        if (null !== $crew) {
            return $this->crewDirectory($crew).'/images/gifts';
        }

        if (null !== $author) {
            return sprintf('users/%d/images/gifts', $author->getId());
        }

        throw new \LogicException('A gift out of any crew always has its author.');
    }

    /**
     * Everything filed under a crew, removed along with it (MoveGiftImageListener).
     */
    public function crewDirectory(Crew $crew): string
    {
        return sprintf('crews/%d', $crew->getId());
    }
}
