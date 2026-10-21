<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveCompetencyRequest;
use App\Http\Resources\CompetencyResource;
use App\Models\Competency;
use App\Services\CompetencyService;
use Illuminate\Http\Request;

/** A teacher's personal competency list. */
class CompetencyController extends Controller
{
    public function __construct(private CompetencyService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Competency::class);

        return CompetencyResource::collection($this->service->list($request->user()));
    }

    public function store(SaveCompetencyRequest $request)
    {
        $this->authorize('create', Competency::class);

        $c = $this->service->create($request->user(), $request->validated());

        return (new CompetencyResource($c->load('subject')))->response()->setStatusCode(201);
    }

    public function update(SaveCompetencyRequest $request, Competency $competency)
    {
        $this->authorize('update', $competency);

        $c = $this->service->update($request->user(), $competency, $request->validated());

        return new CompetencyResource($c->load('subject'));
    }

    public function destroy(Request $request, Competency $competency)
    {
        $this->authorize('delete', $competency);
        $this->service->delete($request->user(), $competency);

        return response()->noContent();
    }
}