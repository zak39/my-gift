<?php

namespace App\Vich;

use Symfony\Bundle\SecurityBundle\Security;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\DirectoryNamerInterface;

final class UserAvatarDirectoryNamer implements DirectoryNamerInterface
{
    public function __construct(private Security $security)
    {
    }

    public function directoryName($object, PropertyMapping $mapping): string
    {
        /** @var App\Entity\User */
        $user = $this->security->getUser();

        return sprintf('users/%d/images/avatar', $user->getId());
    }
}
