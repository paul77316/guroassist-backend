<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\HandlesStudentRecords;
use App\Http\Controllers\Controller;
use App\Http\Resources\DueCommunicationFollowUpResource;
use App\Http\Resources\DueFollowUpResource;
use App\Models\Intervention;
use App\Services\InterventionService;
use App\Services\ParentCommunicationService;
use App\Services\StudentRecordGuard;
use Illuminate\Http\Request;

class FollowUpController extends Controller
{
    use HandlesStudentRecords;

    public function __construct(
        private InterventionService $service,
        private ParentCommunicationService $communications,
        private StudentRecordGuard $guard,
    ) {}

    /**
     * The signed-in user's own follow-ups that are due (follow_up_date <= today in the school timezone,
     * not completed or done, not deleted, not in an archived class, student still in the user's classes).
     * Interventions and parent-contact follow-ups come in one list, told apart by "kind".
     * Rows carry no free text. Also the query the dashboard and notifications will reuse.
     */
    public function due(Request $request)
    {
        $this->authorize('viewAny', Intervention::class);

        $user = $request->user();
        $today = $this->guard->today();
        $relations = ['classAssignment.section.gradeLevel', 'classAssignment.subject'];

        $interventions = $this->service->due($user, $today)->load($relations);
        $communications = $this->communications->due($user, $today)->load($relations);

        $rows = $interventions
            ->map(fn ($r) => (new DueFollowUpResource($r))->resolve($request))
            ->concat($communications->map(fn ($r) => (new DueCommunicationFollowUpResource($r))->resolve($request)))
            ->sortBy([['follow_up_date', 'asc'], ['kind', 'asc'], ['id', 'asc']])
            ->values();

        return response()->json(['data' => $rows, 'meta' => ['today' => $today]]);
    }
}
