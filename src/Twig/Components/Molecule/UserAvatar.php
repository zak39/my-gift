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

    /**
     * Marks the face as the logged-in user's with `data-current-user-avatar`, so that
     * `turbo/avatar.turbo_stream.twig` swaps it after an upload: only for `app.user`, since every
     * marked face gets their new photo. An attribute rather than an id: a page may show the face
     * more than once (the navbar does, desktop dropdown and phone drawer), and an id must be
     * unique (RGAA 8.2).
     */
    public bool $currentUser = false;

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
     * Without a `source`, the template shows the placeholder with the same `alt` and mark.
     *
     * @var array<string, string|true>
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

        $this->imageAttributes = ['alt' => 'Photo de profil '.$this->user->getPseudo()];

        // Without a source (no avatar), the key is left out rather than null.
        $source = $this->avatarUrlGenerator->url($this->user);
        if (null !== $source) {
            $this->imageAttributes['source'] = $source;
        }

        if ($this->currentUser) {
            $this->imageAttributes['data-current-user-avatar'] = true;
        }
    }
}
