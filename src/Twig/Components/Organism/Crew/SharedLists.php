<?php

namespace App\Twig\Components\Organism\Crew;

use App\Entity\Crew;
use App\Entity\User;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * The crews' lists of a user, below their own list: each crew's sections, as on its page.
 * Each crew is the target of its entry in Molecule:SectionNav (`#crew-{id}`).
 */
#[AsTwigComponent]
final class SharedLists
{
    /** @var iterable<Crew> */
    public iterable $crews = [];

    /** Whose page it is: the wording speaks of them on a friend's page. */
    public User $user;

    /**
     * On the owner's own page: a way to create a crew, and a word when there is none yet.
     * The owner is a member of each crew: its drafts show, as on its page.
     */
    public bool $ownerView = false;
}
