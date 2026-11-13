<?php

namespace App\Twig\Components\Organism;

use App\Entity\Crew;
use App\Entity\Gift;
use App\Entity\Section;
use App\Entity\User;
use App\Enum\GiftStatus;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class GiftListHeader
{
    public User $user;

    /** @var iterable<Section> */
    public iterable $sections = [];

    /** @var iterable<Gift> */
    public iterable $giftsWithoutSection = [];

    /**
     * The user's crews: their gifts stay out of the figures, which speak of the user's own
     * list, but the published ones are named beside them.
     *
     * @var iterable<Crew>
     */
    public iterable $crews = [];

    /**
     * The owner manages their own list: unpublished gifts count too, and the
     * stats answer "is my list ready?" instead of "what can I still offer?".
     */
    public bool $ownerView = false;

    /** @var list<Gift>|null */
    private ?array $gifts = null;

    /**
     * Every gift the current viewer may see, flattened across the sections.
     *
     * @return list<Gift>
     */
    private function getGifts(): array
    {
        if (null !== $this->gifts) {
            return $this->gifts;
        }

        $gifts = [];

        foreach ($this->sections as $section) {
            array_push($gifts, ...$section->getVisibleGifts($this->ownerView));
        }

        foreach ($this->giftsWithoutSection as $gift) {
            if ($this->ownerView || $gift->isPublic()) {
                $gifts[] = $gift;
            }
        }

        return $this->gifts = $gifts;
    }

    /** The crews' gifts their friends see, as the page lists them below the user's own. */
    public function getCrewPublishedCount(): int
    {
        $count = 0;

        foreach ($this->crews as $crew) {
            $count += $crew->getGiftCount(false);
        }

        return $count;
    }

    public function getTotal(): int
    {
        return \count($this->getGifts());
    }

    public function getPublishedCount(): int
    {
        return \count(array_filter($this->getGifts(), static fn (Gift $gift) => $gift->isPublic()));
    }

    public function getDraftCount(): int
    {
        return $this->getTotal() - $this->getPublishedCount();
    }

    public function getAvailableCount(): int
    {
        return $this->countStatuses(GiftStatus::CREATED);
    }

    public function getContributingCount(): int
    {
        return $this->countStatuses(GiftStatus::CONTRIBUTING);
    }

    public function getTakenCount(): int
    {
        return $this->countStatuses(GiftStatus::RESERVED, GiftStatus::READY, GiftStatus::GIVEN);
    }

    private function countStatuses(GiftStatus ...$statuses): int
    {
        $count = 0;

        foreach ($this->getGifts() as $gift) {
            if (\in_array($gift->getStatus(), $statuses, true)) {
                ++$count;
            }
        }

        return $count;
    }
}
