<?php

namespace App\Services\Export\Dataset;

use App\Entity\User;
use App\Repository\CrewRepository;
use App\Repository\GiftRepository;
use App\Services\Dto\ExportedDatasetDto;
use App\Services\Dto\ExportedListDto;
use App\Services\Export\GiftRow;
use App\Services\Export\ListName;
use App\Services\Export\OwnerName;

/**
 * The gifts of the lists the user runs, split by list: their own, then their crews' by
 * name, each gift as a GiftRow.
 *
 * A crew without any gift is still a list: a sheet with its header only.
 */
final class GiftsDataset
{
    public function __construct(
        private readonly GiftRepository $giftRepository,
        private readonly CrewRepository $crewRepository,
        private readonly GiftRow $giftRow,
    ) {
    }

    public function of(User $user): ExportedDatasetDto
    {
        // Loaded first: their memberships, fetch-joined here, are what GiftPoolVoter reads
        // for each crew's gift, instead of one query per crew.
        $crews = $this->crewRepository->findForMember($user);

        $rowsByList = ['personal' => []];
        foreach ($this->giftRepository->findInListsOf($user) as $gift) {
            $rowsByList[$gift->getCrew()?->getId() ?? 'personal'][] = $this->giftRow->of($gift, $user);
        }

        $lists = [new ExportedListDto(ListName::own(), $rowsByList['personal'], OwnerName::me())];
        foreach ($crews as $crew) {
            $lists[] = new ExportedListDto(ListName::crew($crew), $rowsByList[$crew->getId()] ?? [], OwnerName::crew($crew));
        }

        return ExportedDatasetDto::split('Cadeaux', 'Propriétaire', GiftRow::COLUMNS, $lists);
    }
}
