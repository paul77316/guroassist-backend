<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\HandlesStudentRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\InterventionEntryRequest;
use App\Http\Requests\SaveInterventionRequest;
use App\Http\Resources\InterventionResource;
use App\Http\Resources\InterventionSummaryResource;
use App\Models\Intervention;
use App\Models\Student;
use App\Services\InterventionService;
use App\Services\StudentRecordGuard;
use App\Support\InterventionStatus;
use App\Support\InterventionVocabulary;
use Illuminate\Http\Request;

class InterventionController extends Controller
{
    use HandlesStudentRecords;

    public function __construct(private InterventionService $service, private StudentRecordGuard $guard) {}

    /** The lists and statuses the screens need for their forms. */
    public function options()
    {
        $this->authorize('viewAny', Intervention::class);

        return response()->json(['data' => [
            'concern_types' => InterventionVocabulary::pairs('CONCERN_TYPES'),
            'intervention_types' => InterventionVocabulary::pairs('INTERVENTION_TYPES'),
            'outcomes' => InterventionVocabulary::pairs('OUTCOMES'),
            'methods' => InterventionVocabulary::pairs('METHODS'),
            'statuses' => array_map(
                fn ($s) => ['value' => $s, 'label' => InterventionStatus::label($s)],
                InterventionStatus::ALL
            ),
        ]]);
    }

    /** A student's interventions (list rows, no free text). trashed=only lists deleted ones. */
    public function index(Request $request, Student $student)
    {
        $this->authorize('view', $student);
        $request->validate([
            'class_assignment_id' => ['nullable', 'integer'],
            'trashed' => ['nullable', 'in:only'],
        ]);

        $rows = $this->service->listForStudent(
            $request->user(),
            $student,
            $request->integer('class_assignment_id') ?: null,
            $request->query('trashed') === 'only'
        );

        return InterventionSummaryResource::collection($this->withContext($rows, $request));
    }

    public function store(SaveInterventionRequest $request, Student $student)
    {
        $this->authorize('create', Intervention::class);

        $intervention = $this->service->create($request->user(), $student, $request->validated());

        return (new InterventionResource($this->detail($intervention, $request)))
            ->response()->setStatusCode(201);
    }

    public function show(Request $request, Intervention $intervention)
    {
        $this->authorize('view', $intervention);
        $this->guard->auditAdminRead($request->user(), $intervention->student, 'intervention', [$intervention->id]);

        return new InterventionResource($this->detail($intervention, $request));
    }

    public function update(SaveInterventionRequest $request, Intervention $intervention)
    {
        $this->authorize('update', $intervention);

        $this->service->update($request->user(), $intervention, $request->validated());

        return new InterventionResource($this->detail($intervention->refresh(), $request));
    }

    public function destroy(Request $request, Intervention $intervention)
    {
        $this->authorize('delete', $intervention);
        $this->service->delete($request->user(), $intervention);

        return response()->noContent();
    }

    public function restore(Request $request, Intervention $intervention)
    {
        $this->authorize('restore', $intervention);

        if (! $intervention->trashed()) {
            return $this->notDeletedResponse('intervention');
        }

        $this->service->restore($request->user(), $intervention);

        return new InterventionResource($this->detail($intervention->refresh(), $request));
    }

    /** Record a follow-up, reschedule, completion or reopen. Returns the updated intervention. */
    public function addFollowup(InterventionEntryRequest $request, Intervention $intervention)
    {
        $this->authorize('addEntry', $intervention);

        $this->service->addEntry($request->user(), $intervention, $request->validated());

        return (new InterventionResource($this->detail($intervention->refresh(), $request)))
            ->response()->setStatusCode(201);
    }

    private function detail(Intervention $intervention, Request $request): Intervention
    {
        return $this->withContext($intervention, $request, ['student', 'followups']);
    }
}
