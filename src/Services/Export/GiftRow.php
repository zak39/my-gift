<?php

namespace App\Services\Export;

use App\Entity\Gift;
use App\Entity\Participant;
use App\Entity\User;
use Symfony\Component\Security\Core\Authorization\UserAuthorizationCheckerInterface;

/**
 * A gift as the export writes it: one row, keyed by column title. A column added here
 * appears in every format.
 *
 * Other users appear by name only, never by email. The viewer owns every gift exported, so
 * the surprise rule of the pages holds here too: GiftPoolVoter decides what they may see
 * of the participants and the pool.
 */
final class GiftRow
{
    /**
     * The keys of every row, in their order: the header of a sheet, even without any gift.
     * A column added to the rows is added here too, which ExportedDatasetDto checks.
     */
    public const COLUMNS = ['Section', 'Titre', 'Description', 'Prix', 'Lien', 'Statut', 'Participants', 'Montant cagnotte', 'Contribution (%)', 'Créé par', 'Créé le'];

    /**
     * In place of what GiftPoolVoter keeps from the gift's owners.
     */
    private const HIDDEN = 'Masqué (surprise)';

    /**
     * The amount and the share of a reservation: no money involved.
     */
    private const NOT_A_POOL = '-';

    public function __construct(
        private readonly UserAuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    /**
     * @return array<string, string|float|int|\DateTimeImmutable|null>
     */
    public function of(Gift $gift, User $viewer): array
    {
        return [
            'Section' => $gift->getSection()?->getTitle() ?? 'Cadeau sans section',
            'Titre' => $gift->getTitle(),
            'Description' => $gift->getDescription(),
            'Prix' => $gift->getPrice(),
            'Lien' => $gift->getLink(),
            'Statut' => $gift->getStatus()?->label(),
            ...$this->participation($gift, $viewer),
            // A crew gift keeps no author once its user deleted their account.
            'Créé par' => $gift->getCreatedBy()?->getFullname() ?? 'Utilisateur supprimé',
            'Créé le' => $gift->getCreatedAt(),
        ];
    }

    /**
     * Who gives the gift and how much the pool collected, as far as the viewer may know:
     * the "Participants", "Montant cagnotte" and "Contribution (%)" columns.
     *
     * @return array{'Participants': ?string, 'Montant cagnotte': string|float|null, 'Contribution (%)': string|int|null}
     */
    private function participation(Gift $gift, User $viewer): array
    {
        if ($gift->getParticipants()->isEmpty()) {
            return [
                'Participants' => null,
                'Montant cagnotte' => null,
                'Contribution (%)' => null,
            ];
        }

        $participants = $this->authorizationChecker->isGrantedForUser($viewer, 'gift.can_see_contributors', $gift)
            ? implode(', ', $gift->getParticipants()->map(fn (Participant $participant) => $participant->getDisplayName())->toArray())
            : self::HIDDEN
        ;
        $pool = $gift->getPool();

        if (!$pool->isPooled()) {
            return [
                'Participants' => $participants,
                'Montant cagnotte' => self::NOT_A_POOL,
                'Contribution (%)' => self::NOT_A_POOL,
            ];
        }

        $canSeeAmounts = $this->authorizationChecker->isGrantedForUser($viewer, 'gift.can_see_amounts', $gift);

        return [
            'Participants' => $participants,
            'Montant cagnotte' => $canSeeAmounts ? $pool->getTotalContributed() : self::HIDDEN,
            'Contribution (%)' => $canSeeAmounts ? $pool->getContributionPercent() : self::HIDDEN,
        ];
    }
}
