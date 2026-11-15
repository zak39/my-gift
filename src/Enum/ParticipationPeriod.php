<?php

namespace App\Enum;

/**
 * Where a reservation or a contribution stands, read from its gift's status: one
 * Molecule:Section each of the user's participations page (`/me/participations`).
 * Cancelling one deletes it, so a participation is always in one of them.
 */
enum ParticipationPeriod: string
{
    case ONGOING = 'ongoing';
    case GIVEN = 'given';
    case ARCHIVED = 'archived';

    /**
     * @return list<GiftStatus>
     */
    public function giftStatuses(): array
    {
        return match ($this) {
            // Reserved, pooled or ready: what GiftStatus calls in progress.
            self::ONGOING => array_values(array_filter(
                GiftStatus::cases(),
                static fn (GiftStatus $status) => $status->isInProgress(),
            )),
            self::GIVEN => [GiftStatus::GIVEN],
            self::ARCHIVED => [GiftStatus::ARCHIVED],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::ONGOING => 'En cours',
            self::GIVEN => 'Offerts',
            self::ARCHIVED => 'Archivés',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ONGOING => 'Les cadeaux réservés, en cagnotte ou prêts à offrir.',
            self::GIVEN => 'Les cadeaux remis à la personne qui les attendait.',
            self::ARCHIVED => 'Les cadeaux que leur destinataire a rangés.',
        };
    }

    /**
     * Named here, not read from giftStatuses(): « En cours » would take the icon of
     * whichever status GiftStatus happens to declare first.
     */
    public function icon(): string
    {
        return match ($this) {
            self::ONGOING => 'lucide:hand-heart',
            self::GIVEN => GiftStatus::GIVEN->icon(),
            self::ARCHIVED => GiftStatus::ARCHIVED->icon(),
        };
    }

    /**
     * Id of the period's Molecule:Section, its Sommaire entry's link target.
     */
    public function anchor(): string
    {
        return 'participations-'.$this->value;
    }

    public function emptyMessage(): string
    {
        return match ($this) {
            self::ONGOING => 'Aucune réservation ni contribution en cours.',
            self::GIVEN => 'Aucun cadeau offert pour le moment.',
            self::ARCHIVED => 'Aucun cadeau archivé.',
        };
    }
}
