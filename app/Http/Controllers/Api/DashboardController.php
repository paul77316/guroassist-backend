<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TodaySummaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /** Counts only. Admins get {applies: false}. */
    public function today(Request $request, TodaySummaryService $service): JsonResponse
    {
        return response()->json(['data' => $service->summary($request->user())]);
    }
}
