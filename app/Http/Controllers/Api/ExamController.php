<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\HandlesStudentRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveExamRequest;
use App\Http\Resources\ExamResource;
use App\Http\Resources\ExamSetResource;
use App\Http\Resources\ExamSummaryResource;
use App\Models\Exam;
use App\Services\ExamImageService;
use App\Services\ExamQuestionService;
use App\Services\ExamService;
use Illuminate\Http\Request;

/** Exams and their sets. An exam holds no student data. */
class ExamController extends Controller
{
    use HandlesStudentRecords;

    public function __construct(private ExamService $service) {}

    public function options()
    {
        $this->authorize('viewAny', Exam::class);

        return response()->json(['data' => [
            'choice_counts' => ExamService::CHOICE_COUNTS,
            'max_questions' => ExamQuestionService::MAX_QUESTIONS,
            'max_sets' => ExamService::MAX_SETS,
            'image' => [
                'max_kb' => 2048,
                'types' => ['png', 'jpg', 'webp'],
                'max_width' => ExamImageService::MAX_WIDTH,
            ],
        ]]);
    }

    /** List rows carry counts and no question text. */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Exam::class);
        $request->validate([
            'class_assignment_id' => ['nullable', 'integer'],
            'trashed' => ['nullable', 'in:only'],
        ]);

        $rows = $this->service->list(
            $request->user(),
            $request->integer('class_assignment_id') ?: null,
            $request->query('trashed') === 'only'
        );

        return ExamSummaryResource::collection($this->withContext($rows, $request));
    }

    public function store(SaveExamRequest $request)
    {
        $this->authorize('create', Exam::class);

        $exam = $this->service->create($request->user(), $request->validated());

        return (new ExamResource($this->detail($exam, $request)))->response()->setStatusCode(201);
    }

    public function show(Request $request, Exam $exam)
    {
        $this->authorize('view', $exam);
        $this->service->auditRead($request->user(), $exam);

        return new ExamResource($this->detail($exam, $request));
    }

    public function update(SaveExamRequest $request, Exam $exam)
    {
        $this->authorize('update', $exam);

        $this->service->update($request->user(), $exam, $request->validated());

        return new ExamResource($this->detail($exam->refresh(), $request));
    }

    public function destroy(Request $request, Exam $exam)
    {
        $this->authorize('delete', $exam);
        $this->service->delete($request->user(), $exam);

        return response()->noContent();
    }

    public function restore(Request $request, Exam $exam)
    {
        $this->authorize('restore', $exam);

        if (! $exam->trashed()) {
            return $this->notDeletedResponse('exam');
        }

        $this->service->restore($request->user(), $exam);

        return new ExamResource($this->detail($exam->refresh(), $request));
    }

    public function addSet(Request $request, Exam $exam)
    {
        $this->authorize('update', $exam);

        $set = $this->service->addSet($request->user(), $exam);

        return (new ExamSetResource($set->loadCount('questions')))->response()->setStatusCode(201);
    }

    /** Class, counts and the sets (each with its question count), loaded in a fixed number of queries. */
    private function detail(Exam $exam, Request $request): Exam
    {
        $this->withContext($exam, $request);
        $exam->load(['sets' => fn ($q) => $q->withCount('questions')->orderBy('label')]);
        $exam->loadCount(['sets', 'questions']);

        return $exam;
    }
}