<?php

namespace App\Vich;

use Symfony\Bundle\SecurityBundle\Security;
use Vich\UploaderBundle\Naming\DirectoryNamerInterface;
use Vich\UploaderBundle\Mapping\PropertyMapping;

final class UserGiftDirectoryNamer implements DirectoryNamerInterface
{
    public function __construct(private Security $security)
    {
    }

    public function directoryName($object, PropertyMapping $mapping): string
    {
        /** @var App\Entity\User */
        $user = $this->security->getUser();

        return sprintf('users/%d/images/gifts', $user->getId());
    }
}
