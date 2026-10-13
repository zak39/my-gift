<?php

namespace App\Services\Export;

use App\Entity\User;
use App\Repository\CrewRepository;
use App\Repository\GiftRepository;
use App\Services\Dto\ExportedListDto;

/**
 * The user's lists, their own then their crews' by name, each gift as a GiftRow: what
 * every export format writes.
 *
 * A crew without any gift is still a list: a sheet with its header only.
 */
final class GiftLists
{
    public function __construct(
        private readonly GiftRepository $giftRepository,
        private readonly CrewRepository $crewRepository,
        private readonly GiftRow $giftRow,
    ) {
    }

    /**
     * @return list<ExportedListDto>
     */
    public function of(User $user): array
    {
        // Loaded first: their memberships, fetch-joined here, are what GiftPoolVoter reads
        // for each crew's gift, instead of one query per crew.
        $crews = $this->crewRepository->findForMember($user);

        $rowsByList = ['personal' => []];
        foreach ($this->giftRepository->findInListsOf($user) as $gift) {
            $rowsByList[$gift->getCrew()?->getId() ?? 'personal'][] = $this->giftRow->of($gift, $user);
        }

        $lists = [new ExportedListDto('Mes cadeaux', $rowsByList['personal'])];
        foreach ($crews as $crew) {
            $lists[] = new ExportedListDto('Groupe '.$crew->getName(), $rowsByList[$crew->getId()] ?? []);
        }

        return $lists;
    }
}
