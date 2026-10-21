<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Intervention;
use App\Services\StudentsToReviewService;
use Illuminate\Http\Request;

class StudentsToReviewController extends Controller
{
    /**
     * The signed-in teacher's students who match a review prompt in any of their active classes,
     * one row per student. Read-only: it creates nothing. Admins have no classes of their own, so they get an empty list.
     */
    public function index(Request $request, StudentsToReviewService $service)
    {
        $this->authorize('viewAny', Intervention::class);

        $result = $service->forUser($request->user());

        return response()->json([
            'data' => $result['students'],
            'meta' => [
                'classes' => $result['classes'],
                'period' => $result['period'],
                'today' => $result['today'],
                'handled_days' => $result['handled_days'],
            ],
        ]);
    }

    /**
     * The signed-in user's own open interventions, keyed by student id:
     * { "12": { "active_count": 1, "follow_up_due": true } }. Counts only, no text.
     * The two report pages use it for the "Intervention active" indicator.
     */
    public function activeInterventions(Request $request, StudentsToReviewService $service)
    {
        $this->authorize('viewAny', Intervention::class);

        // (object) so an empty result is {} and not [].
        return response()->json(['data' => (object) $service->activeInterventions($request->user())]);
    }
}
