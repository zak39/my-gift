<?php

namespace App\Twig\Components\Molecule;

use App\Entity\User;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PostMount;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

/**
 * A user's avatar, falling back to the Dicebear default when they never uploaded one.
 *
 * The fallback and the alt text live here so that the avatar asset lookup is written once
 * instead of at every place that shows a face.
 */
#[AsTwigComponent]
final class UserAvatar
{
    public User $user;

    /** Tailwind utilities for the avatar frame: size, ring, shrink. */
    public string $sizeClass = '';

    /** Turbo Stream target, for the pages that swap the avatar in place after an upload. */
    public ?string $id = null;

    /**
     * Attributes for the frame and the image, spread by the template.
     *
     * Derived rather than passed, but they have to be props: the template hands the image to
     * `molecule:Avatar` through a block, and inside a block `this` is rebound to the
     * receiving component, so a getter on this class would be out of reach there. Props do
     * carry over.
     *
     * @var array<string, string>
     */
    public array $frameAttributes = [];

    /** @var array<string, string> */
    public array $imageAttributes = [];

    public function __construct(private readonly UploaderHelper $uploaderHelper)
    {
    }

    #[PostMount]
    public function buildAttributes(): void
    {
        // daisyUI sizes the image off the frame — `.avatar > div` is `aspect-ratio: 1` and
        // `.avatar img` is `width/height: 100%` — so the frame alone carries the size.
        // Left empty rather than `class: ''`, which would override the merged `avatar` class.
        $this->frameAttributes = '' === $this->sizeClass ? [] : ['class' => $this->sizeClass];

        $this->imageAttributes = [
            'source' => $this->uploaderHelper->asset($this->user, $this->user->getAvatarField()) ?? '',
            'alt' => 'Photo de profil '.$this->user->getFullname(),
        ];

        if (null !== $this->id) {
            $this->imageAttributes['id'] = $this->id;
        }
    }
}
