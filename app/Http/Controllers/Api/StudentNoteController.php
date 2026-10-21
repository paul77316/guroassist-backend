<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\HandlesStudentRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveStudentNoteRequest;
use App\Http\Resources\StudentNoteResource;
use App\Http\Resources\StudentNoteSummaryResource;
use App\Models\Student;
use App\Models\StudentNote;
use App\Services\StudentNoteService;
use App\Services\StudentRecordGuard;
use Illuminate\Http\Request;

/** Private teacher notes: visible only to the author and admins. */
class StudentNoteController extends Controller
{
    use HandlesStudentRecords;

    public function __construct(private StudentNoteService $service, private StudentRecordGuard $guard) {}

    /** List rows carry a short preview only. */
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

        return StudentNoteSummaryResource::collection($this->withContext($rows, $request));
    }

    public function store(SaveStudentNoteRequest $request, Student $student)
    {
        $this->authorize('create', StudentNote::class);

        $note = $this->service->create($request->user(), $student, $request->validated());

        return (new StudentNoteResource($this->withContext($note, $request)))->response()->setStatusCode(201);
    }

    public function show(Request $request, StudentNote $note)
    {
        $this->authorize('view', $note);
        $this->guard->auditAdminRead($request->user(), $note->student, 'student_note', [$note->id]);

        return new StudentNoteResource($this->withContext($note, $request));
    }

    public function update(SaveStudentNoteRequest $request, StudentNote $note)
    {
        $this->authorize('update', $note);

        $this->service->update($request->user(), $note, $request->validated());

        return new StudentNoteResource($this->withContext($note->refresh(), $request));
    }

    public function destroy(Request $request, StudentNote $note)
    {
        $this->authorize('delete', $note);
        $this->service->delete($request->user(), $note);

        return response()->noContent();
    }

    public function restore(Request $request, StudentNote $note)
    {
        $this->authorize('restore', $note);

        if (! $note->trashed()) {
            return $this->notDeletedResponse('note');
        }

        $this->service->restore($request->user(), $note);

        return new StudentNoteResource($this->withContext($note->refresh(), $request));
    }
}
