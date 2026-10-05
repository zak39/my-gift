<?php

namespace App\Twig\Components\Molecule\Gift;

use App\Entity\Gift;
use App\Enum\GiftStatus;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * The gift_request workflow told as a journey: the second step names itself after
 * the way the gift is being offered (a single reservation or a pool).
 */
#[AsTwigComponent]
final class Journey
{
    public Gift $gift;

    /**
     * @return list<array{label: string, icon: string}>
     */
    public function getSteps(): array
    {
        return [
            ['label' => 'Dans la liste', 'icon' => 'lucide:list-plus'],
            $this->gift->getPool()->isPooled()
                ? ['label' => 'Cagnotte', 'icon' => 'lucide:piggy-bank']
                : ['label' => 'Réservé', 'icon' => 'lucide:bookmark-check'],
            ['label' => 'Prêt à offrir', 'icon' => 'lucide:package-check'],
            ['label' => 'Offert', 'icon' => 'lucide:party-popper'],
        ];
    }

    /**
     * Index in getSteps() of the step the gift currently sits on.
     */
    public function getCurrentStep(): int
    {
        return match ($this->gift->getStatus()) {
            GiftStatus::CREATED => 0,
            GiftStatus::CONTRIBUTING => 1,
            GiftStatus::RESERVED => 1,
            GiftStatus::READY => 2,
            GiftStatus::GIVEN => 3,
            GiftStatus::ARCHIVED => 3,
        };
    }
}
