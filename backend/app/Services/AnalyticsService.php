<?php

namespace App\Services;

use App\Repositories\AnalyticsRepository;

readonly class AnalyticsService
{
    public function __construct(private AnalyticsRepository $analyticsRepository)
    {
    }

    public function overview(?string $from = null, ?string $to = null): array
    {
        return [
            'total_proposals' => $this->analyticsRepository->totalProposals(),
            'period_proposals' => $this->analyticsRepository->proposalsCountBetween($from, $to),
            'answered_share' => round($this->analyticsRepository->answeredShare(), 2),
            'avg_response_time_seconds' => $this->analyticsRepository->averageResponseSeconds(),
        ];
    }

    public function byPeriod(string $granularity = 'month', ?string $from = null, ?string $to = null): array
    {
        return $this->analyticsRepository->proposalsByPeriod($granularity, $from, $to);
    }

    public function byCategory(int $limit = 10): array
    {
        return $this->analyticsRepository->proposalsByCategory($limit);
    }

    public function byCity(int $limit = 10): array
    {
        return $this->analyticsRepository->proposalsByCity($limit);
    }
}


