<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExamSetResource;
use App\Models\ExamSet;
use App\Services\ExamService;
use Illuminate\Http\Request;

/** One set with its questions (full text and the answer key). */
class ExamSetController extends Controller
{
    public function __construct(private ExamService $service) {}

    public function show(Request $request, ExamSet $set)
    {
        $this->authorize('view', $set);

        $set->load(['exam', 'questions.competency']);
        $this->service->auditRead($request->user(), $set->exam);

        return new ExamSetResource($set);
    }

    public function destroy(Request $request, ExamSet $set)
    {
        $this->authorize('delete', $set);
        $this->service->deleteSet($request->user(), $set);

        return response()->noContent();
    }
}