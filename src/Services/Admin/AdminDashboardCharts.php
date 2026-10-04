<?php

namespace App\Services\Admin;

use App\Repository\GiftRepository;
use App\Repository\UserRepository;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

/**
 * The charts of the admin dashboard: how many accounts and gifts were created each day
 * over the last DAYS days, today included.
 */
class AdminDashboardCharts
{
    public const DAYS = 30;

    public function __construct(
        private UserRepository $userRepository,
        private GiftRepository $giftRepository,
        private ChartBuilderInterface $chartBuilder,
    ) {
    }

    public function usersCreatedPerDay(): Chart
    {
        $since = $this->since();

        return $this->createdPerDayChart(
            $this->fillMissingDays($this->userRepository->countCreatedPerDay($since), $since),
            'Inscriptions',
        );
    }

    public function giftsCreatedPerDay(): Chart
    {
        $since = $this->since();

        return $this->createdPerDayChart(
            $this->fillMissingDays($this->giftRepository->countCreatedPerDay($since), $since),
            'Cadeaux créés',
        );
    }

    /**
     * Midnight, DAYS - 1 days ago: with today, that makes DAYS full days.
     */
    private function since(): \DateTimeImmutable
    {
        return new \DateTimeImmutable(sprintf('-%d days midnight', self::DAYS - 1));
    }

    /**
     * The repositories only return the days on which something was created; a chart
     * spaces its labels evenly, so every missing day must be there with 0.
     *
     * @param array<string, int> $counts keyed by day ('Y-m-d')
     *
     * @return array<string, int> one entry per day from $since to today, keyed by 'd/m'
     */
    private function fillMissingDays(array $counts, \DateTimeImmutable $since): array
    {
        $days = [];
        $current = $since;
        $today = new \DateTimeImmutable('midnight');
        while ($current <= $today) {
            $days[$current->format('d/m')] = $counts[$current->format('Y-m-d')] ?? 0;
            $current = $current->modify('+1 day');
        }

        return $days;
    }

    /**
     * @param array<string, int> $perDay
     */
    private function createdPerDayChart(array $perDay, string $label): Chart
    {
        return $this->chartBuilder
            ->createChart(Chart::TYPE_BAR)
            ->setData([
                'labels' => array_keys($perDay),
                'datasets' => [
                    [
                        'label' => $label,
                        'data' => array_values($perDay),
                    ],
                ],
            ])
            ->setOptions([
                'plugins' => ['legend' => ['display' => false]],
                'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
            ])
        ;
    }
}
