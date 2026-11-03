<?php

namespace App\Twig\Components\Molecule;

use App\Entity\User;
use App\Services\Url\AvatarUrlGenerator;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PostMount;

/**
 * A user's avatar (AvatarUrlGenerator::url()), or Molecule:User:AvatarPlaceholder without one.
 *
 * The choice between them and the alt text live here, so that every page showing a face
 * renders it alike.
 */
#[AsTwigComponent]
final class UserAvatar
{
    public User $user;

    /**
     * Tailwind utilities for the avatar frame: size, ring, shrink. Empty, the frame is `w-10`,
     * the size of a btn-circle: unsized, the placeholder, unlike an image, would have no size.
     */
    public string $sizeClass = '';

    /** Turbo Stream target, for the pages that swap the avatar in place after an upload. */
    public ?string $id = null;

    /**
     * Attributes for the frame and the image, spread by the template.
     *
     * Derived rather than passed, but they have to be props: the template hands the image to
     * `Molecule:Avatar` through a block, and inside a block `this` is rebound to the
     * receiving component, so a getter on this class would be out of reach there. Props do
     * carry over.
     *
     * @var array<string, string>
     */
    public array $frameAttributes = [];

    /**
     * Without a `source`, the template shows the placeholder with the same `alt` and `id`.
     *
     * @var array<string, string>
     */
    public array $imageAttributes = [];

    public function __construct(private readonly AvatarUrlGenerator $avatarUrlGenerator)
    {
    }

    #[PostMount]
    public function buildAttributes(): void
    {
        // daisyUI sizes the image off the frame — `.avatar > div` is `aspect-ratio: 1` and
        // `.avatar img` is `width/height: 100%` — so the frame alone carries the size.
        $this->frameAttributes = ['class' => '' !== $this->sizeClass ? $this->sizeClass : 'w-10'];

        // Without a source (no avatar) or an id, the key is left out rather than null.
        $this->imageAttributes = array_filter(
            [
                'source' => $this->avatarUrlGenerator->url($this->user),
                'alt' => 'Photo de profil '.$this->user->getFullname(),
                'id' => $this->id,
            ],
            fn (?string $value) => null !== $value,
        );
    }
}
