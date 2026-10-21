<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveSubmissionsRequest;
use App\Http\Requests\StoreAssignmentRequest;
use App\Http\Requests\UpdateAssignmentRequest;
use App\Http\Resources\AssignmentResource;
use App\Models\Assignment;
use App\Models\ClassAssignment;
use App\Services\AssignmentService;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function __construct(private AssignmentService $service) {}

    /** Assignments of one class with counts. scope: all | upcoming | past; trashed=only lists deleted ones. */
    public function index(Request $request)
    {
        $request->validate([
            'scope' => ['nullable', 'in:all,upcoming,past'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'trashed' => ['nullable', 'in:only'],
        ]);
        $ca = $this->classFrom($request);

        $items = collect($this->service->listForClass(
            $ca,
            $request->query('scope', 'all'),
            $request->query('from'),
            $request->query('to'),
            $request->query('trashed') === 'only'
        ))->map(
            fn ($i) => (new AssignmentResource($i['assignment']))->resolve() + ['stats' => $i['stats']]
        )->values();

        return response()->json(['data' => $items]);
    }

    /** Students with missing work in one class. */
    public function missing(Request $request)
    {
        return response()->json(['data' => $this->service->missing($this->classFrom($request))]);
    }

    public function store(StoreAssignmentRequest $request)
    {
        $ca = ClassAssignment::withTrashed()->findOrFail($request->class_assignment_id);
        $this->authorize('view', $ca); // the class's teacher or an admin

        $assignment = $this->service->create($ca, $request->validated(), $request->user());

        return (new AssignmentResource($assignment))->response()->setStatusCode(201);
    }

    /** One assignment with every student's status (and the private feedback). */
    public function show(Assignment $assignment)
    {
        $this->authorize('view', $assignment);

        return response()->json(['data' => $this->payload($assignment)]);
    }

    public function update(UpdateAssignmentRequest $request, Assignment $assignment)
    {
        $this->authorize('update', $assignment);

        $result = $this->service->update($assignment, $request->validated(), $request->user());

        return (new AssignmentResource($result['assignment']))
            ->additional(['meta' => ['recalculated' => $result['recalculated']]]);
    }

    public function destroy(Request $request, Assignment $assignment)
    {
        $this->authorize('delete', $assignment);
        $this->service->delete($assignment, $request->user());

        return response()->noContent();
    }

    public function restore(Request $request, Assignment $assignment)
    {
        $this->authorize('restore', $assignment);

        if (! $assignment->trashed()) {
            $msg = 'This assignment is not deleted.';

            return response()->json(['message' => $msg, 'errors' => ['assignment' => [$msg]]], 422);
        }

        $this->service->restore($assignment, $request->user());

        return new AssignmentResource($assignment->refresh());
    }

    /** One bulk save for the whole assignment, like attendance. Also used for corrections. */
    public function saveSubmissions(SaveSubmissionsRequest $request, Assignment $assignment)
    {
        $this->authorize('recordSubmissions', $assignment);

        $changed = $this->service->saveSubmissions($assignment, $request->validated()['records'], $request->user());

        return response()->json([
            'message' => 'Saved.',
            'changed' => $changed,
            'data' => $this->payload($assignment->refresh()),
        ]);
    }

    private function classFrom(Request $request): ClassAssignment
    {
        $data = $request->validate([
            'class_assignment_id' => ['required', 'integer', 'exists:class_assignments,id'],
        ]);

        // withTrashed: an archived class stays readable
        $ca = ClassAssignment::withTrashed()->findOrFail($data['class_assignment_id']);
        $this->authorize('view', $ca);

        return $ca;
    }

    private function payload(Assignment $assignment): array
    {
        $grid = $this->service->grid($assignment);

        return [
            'assignment' => (new AssignmentResource($assignment))->resolve(),
            'students' => $grid['students'],
            'stats' => $grid['stats'],
        ];
    }
}
