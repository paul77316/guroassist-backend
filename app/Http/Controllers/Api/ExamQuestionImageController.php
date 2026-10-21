<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExamImageRequest;
use App\Http\Resources\ExamQuestionResource;
use App\Models\ExamQuestion;
use App\Services\ExamImageService;
use Illuminate\Http\Request;

/** The image of one question. It is streamed only to people the exam policy allows. */
class ExamQuestionImageController extends Controller
{
    public function __construct(private ExamImageService $images) {}

    public function store(ExamImageRequest $request, ExamQuestion $question)
    {
        $this->authorize('update', $question);

        $q = $this->images->store(
            $request->user(),
            $question,
            $request->file('image'),
            $request->input('alt')
        );

        return new ExamQuestionResource($q->load('competency'));
    }

    public function show(Request $request, ExamQuestion $question)
    {
        $this->authorize('view', $question);

        return $this->images->response($question)
            ?? response()->json(['message' => 'This question has no image.'], 404);
    }

    public function destroy(Request $request, ExamQuestion $question)
    {
        $this->authorize('delete', $question);
        $this->images->remove($request->user(), $question);

        return response()->noContent();
    }
}