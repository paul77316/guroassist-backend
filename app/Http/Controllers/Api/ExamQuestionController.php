<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderExamQuestionsRequest;
use App\Http\Requests\SaveExamQuestionRequest;
use App\Http\Resources\ExamQuestionResource;
use App\Http\Resources\ExamSetResource;
use App\Models\ExamQuestion;
use App\Models\ExamSet;
use App\Services\ExamQuestionService;
use Illuminate\Http\Request;

/** Questions inside a set. */
class ExamQuestionController extends Controller
{
    public function __construct(private ExamQuestionService $service) {}

    public function store(SaveExamQuestionRequest $request, ExamSet $set)
    {
        $this->authorize('update', $set);

        $q = $this->service->create($request->user(), $set, $request->validated());

        return (new ExamQuestionResource($q->load('competency')))->response()->setStatusCode(201);
    }

    public function update(SaveExamQuestionRequest $request, ExamQuestion $question)
    {
        $this->authorize('update', $question);

        $q = $this->service->update($request->user(), $question, $request->validated());

        return new ExamQuestionResource($q->load('competency'));
    }

    public function destroy(Request $request, ExamQuestion $question)
    {
        $this->authorize('delete', $question);
        $this->service->delete($request->user(), $question);

        return response()->noContent();
    }

    public function reorder(ReorderExamQuestionsRequest $request, ExamSet $set)
    {
        $this->authorize('update', $set);

        $this->service->reorder($request->user(), $set, $request->validated()['ids']);

        return new ExamSetResource($set->load(['exam', 'questions.competency']));
    }
}