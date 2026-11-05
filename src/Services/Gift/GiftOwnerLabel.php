<?php

namespace App\Services\Gift;

use App\Entity\Gift;

/**
 * Whose gift it is, as its givers' emails say it: its author's pseudo, or its crew for
 * a crew's gift, whoever added it (the author may be gone, GiftOwnership).
 *
 * Plain text worked out when the email is queued: the worker renders it later, once the
 * gift's crew or author may be gone, as the crew emails' contexts (CrewNotificationSubscriber).
 *
 * A service rather than static methods: the wording may one day depend on the reader's
 * language, through an injected translator.
 */
final class GiftOwnerLabel
{
    /**
     * « Jon », or « le groupe « Pour Timéo » ».
     */
    public function name(Gift $gift): string
    {
        $crew = $gift->getCrew();

        return null !== $crew
            ? "le groupe « {$crew->getName()} »"
            : $gift->getCreatedBy()->getPseudo()
        ;
    }

    /**
     * « de Jon », or « du groupe « Pour Timéo » »: what follows « le cadeau ».
     */
    public function of(Gift $gift): string
    {
        if (null !== $gift->getCrew()) {
            return 'du groupe « '.$gift->getCrew()->getName().' »';
        }

        return 'de '.$this->name($gift);
    }
}
