<?php

namespace App\Repositories;

use App\Models\Proposal;
use App\Models\ProposalResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsRepository
{
    public function totalProposals(): int
    {
        return Proposal::count();
    }

    public function proposalsCountBetween(?string $from, ?string $to): int
    {
        return Proposal::query()
            ->when($from, fn($q) => $q->where('created_at', '>=', Carbon::parse($from)->startOfDay()))
            ->when($to, fn($q) => $q->where('created_at', '<=', Carbon::parse($to)->endOfDay()))
            ->count();
    }

    public function answeredShare(): float
    {
        $total = Proposal::count();
        if ($total === 0) {
            return 0.0;
        }

        $answered = ProposalResponse::distinct('proposal_id')->count('proposal_id');
        return ($answered / $total) * 100.0;
    }

    public function averageResponseSeconds(): ?float
    {
        // Average seconds from proposal.created_at to first response.created_at
        $row = DB::table('proposal_responses as pr')
            ->join('proposals as p', 'p.id', '=', 'pr.proposal_id')
            ->select(DB::raw('AVG(EXTRACT(EPOCH FROM (pr.created_at - p.created_at))) as avg_seconds'))
            ->first();

        return $row && $row->avg_seconds !== null ? (float)$row->avg_seconds : null;
    }

    public function proposalsByPeriod(string $granularity = 'month', ?string $from = null, ?string $to = null): array
    {
        // Use PostgreSQL date_trunc for week/month; for day use ::date
        $select = match ($granularity) {
            'day' => "DATE(p.created_at) as period",
            'week' => "DATE_TRUNC('week', p.created_at) as period",
            default => "DATE_TRUNC('month', p.created_at) as period",
        };

        $rows = DB::table('proposals as p')
            ->when($from, fn($q) => $q->where('p.created_at', '>=', $from))
            ->when($to, fn($q) => $q->where('p.created_at', '<=', $to))
            ->selectRaw($select)
            ->selectRaw('COUNT(*) as count')
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return $rows->map(fn($r) => [
            'period' => (string)$r->period,
            'count' => (int)$r->count,
        ])->all();
    }

    public function proposalsByCategory(int $limit = 10): array
    {
        $rows = DB::table('proposals as p')
            ->join('categories as c', 'c.id', '=', 'p.category_id')
            ->select('c.name as category')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('c.name')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();

        return $rows->map(fn($r) => [
            'category' => $r->category,
            'count' => (int)$r->count,
        ])->all();
    }

    public function proposalsByCity(int $limit = 10): array
    {
        $rows = DB::table('proposals as p')
            ->join('cities as c', 'c.id', '=', 'p.city_id')
            ->select('c.name as city')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('c.name')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();

        return $rows->map(fn($r) => [
            'city' => $r->city,
            'count' => (int)$r->count,
        ])->all();
    }
}


