<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsService;
use Illuminate\Http\Request;

/**
 * @group Analytics
 */
class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $analyticsService)
    {
    }

    public function overview(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');
        return response()->json($this->analyticsService->overview($from, $to));
    }

    public function byPeriod(Request $request)
    {
        $granularity = $request->query('granularity', 'month');
        $from = $request->query('from');
        $to = $request->query('to');
        return response()->json([
            'data' => $this->analyticsService->byPeriod($granularity, $from, $to)
        ]);
    }

    public function byCategory(Request $request)
    {
        $limit = (int)$request->query('limit', 10);
        return response()->json([
            'data' => $this->analyticsService->byCategory($limit)
        ]);
    }

    public function byCity(Request $request)
    {
        $limit = (int)$request->query('limit', 10);
        return response()->json([
            'data' => $this->analyticsService->byCity($limit)
        ]);
    }
}


