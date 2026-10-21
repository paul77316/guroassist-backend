<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InterventionListResource;
use App\Models\Intervention;
use App\Services\InterventionListService;
use App\Support\InterventionStatus;
use App\Support\InterventionVocabulary;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InterventionListController extends Controller
{
    /**
     * The signed-in teacher's own interventions across students, 25 per page. Read-only, no free text.
     * status: active (not completed) | planned | in_progress | requires_follow_up | completed
     */
    public function index(Request $request, InterventionListService $service)
    {
        $this->authorize('viewAny', Intervention::class);

        $filters = $request->validate([
            'status' => ['nullable', Rule::in(array_merge(['active'], InterventionStatus::ALL))],
            'class_assignment_id' => ['nullable', 'integer'],
            'concern_type' => ['nullable', Rule::in(InterventionVocabulary::keys('CONCERN_TYPES'))],
            'outcome' => ['nullable', Rule::in(InterventionVocabulary::keys('OUTCOMES'))],
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        return InterventionListResource::collection($service->paginate($request->user(), $filters));
    }
}
