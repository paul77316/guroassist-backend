<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\HandlesStudentRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveParentCommunicationRequest;
use App\Http\Resources\ParentCommunicationResource;
use App\Http\Resources\ParentCommunicationSummaryResource;
use App\Models\ParentCommunication;
use App\Models\Student;
use App\Services\ParentCommunicationService;
use App\Services\StudentRecordGuard;
use Illuminate\Http\Request;

/** Log of parent contact the teacher already made. It sends nothing. */
class ParentCommunicationController extends Controller
{
    use HandlesStudentRecords;

    public function __construct(private ParentCommunicationService $service, private StudentRecordGuard $guard) {}

    /** List rows have no reason or summary text. */
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

        return ParentCommunicationSummaryResource::collection($this->withContext($rows, $request));
    }

    public function store(SaveParentCommunicationRequest $request, Student $student)
    {
        $this->authorize('create', ParentCommunication::class);

        $comm = $this->service->create($request->user(), $student, $request->validated());

        return (new ParentCommunicationResource($this->withContext($comm, $request)))->response()->setStatusCode(201);
    }

    public function show(Request $request, ParentCommunication $communication)
    {
        $this->authorize('view', $communication);
        $this->guard->auditAdminRead($request->user(), $communication->student, 'parent_communication', [$communication->id]);

        return new ParentCommunicationResource($this->withContext($communication, $request));
    }

    public function update(SaveParentCommunicationRequest $request, ParentCommunication $communication)
    {
        $this->authorize('update', $communication);

        $this->service->update($request->user(), $communication, $request->validated());

        return new ParentCommunicationResource($this->withContext($communication->refresh(), $request));
    }

    public function destroy(Request $request, ParentCommunication $communication)
    {
        $this->authorize('delete', $communication);
        $this->service->delete($request->user(), $communication);

        return response()->noContent();
    }

    public function restore(Request $request, ParentCommunication $communication)
    {
        $this->authorize('restore', $communication);

        if (! $communication->trashed()) {
            return $this->notDeletedResponse('communication');
        }

        $this->service->restore($request->user(), $communication);

        return new ParentCommunicationResource($this->withContext($communication->refresh(), $request));
    }
        public function markFollowUpDone(Request $request, ParentCommunication $communication)
    {
        $this->authorize('update', $communication);

        $row = $this->service->markFollowUpDone($request->user(), $communication);

        return new ParentCommunicationResource($this->withContext($row, $request));
    }

    public function undoFollowUpDone(Request $request, ParentCommunication $communication)
    {
        $this->authorize('update', $communication);

        $row = $this->service->undoFollowUpDone($request->user(), $communication);

        return new ParentCommunicationResource($this->withContext($row, $request));
    }
}
