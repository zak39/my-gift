<?php

namespace App\Services\Url;

use App\Entity\User;
use Symfony\Component\Asset\Packages;
use Twig\Attribute\AsTwigFunction;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

/**
 * A user's avatar: the one they uploaded, or else their DiceBear one, if any
 * (User::getAvatarField()).
 */
final class AvatarUrlGenerator
{
    /** The lucide `user-round` icon of the pages, as an image for where an icon cannot go. */
    private const PLACEHOLDER = 'images/avatar-placeholder.png';

    public function __construct(
        private readonly UploaderHelper $uploaderHelper,
        private readonly Packages $packages,
    ) {
    }

    /**
     * Null without an avatar: the pages draw the placeholder icon themselves
     * (Molecule:UserAvatar).
     */
    #[AsTwigFunction('user_avatar_url')]
    public function url(User $user): ?string
    {
        $field = $user->getAvatarField();

        return null !== $field ? $this->uploaderHelper->asset($user, $field) : null;
    }

    /**
     * Always an image, the placeholder one without an avatar: for the emails and the admin,
     * which cannot render the icon.
     */
    #[AsTwigFunction('user_avatar_image_url')]
    public function imageUrl(User $user): string
    {
        return $this->url($user) ?? $this->packages->getUrl(self::PLACEHOLDER);
    }
}
