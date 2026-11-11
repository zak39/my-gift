<?php

namespace App\Twig\Components\Molecule\Friend;

use App\Entity\User;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * One person in a list of « Mes ami·es », a friend, a request or a blocked user: their face,
 * their name, the friendship buttons, and the way to their sections for whoever may open
 * them. A row of a daisyUI list, as Molecule:Crew:MemberRow is in a crew's.
 */
#[AsTwigComponent]
final class Row
{
    public User $user;

    /**
     * Required through mount(): the template reads the user throughout, so a missing one
     * would only fail further down, in path(), with a 500, instead of a clear error here.
     */
    public function mount(User $user): void
    {
        $this->user = $user;
    }
}
