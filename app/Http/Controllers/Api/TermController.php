<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTermRequest;
use App\Http\Requests\UpdateTermRequest;
use App\Http\Resources\TermResource;
use App\Models\Term;
use App\Services\TermService;
use Illuminate\Http\Request;

class TermController extends Controller
{
    public function __construct(private TermService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Term::class);

        return TermResource::collection(
            Term::query()
                ->when($request->academic_year_id, fn ($q, $id) => $q->where('academic_year_id', $id))
                ->orderBy('academic_year_id')->orderBy('sort_order')->get()
        );
    }

    public function store(StoreTermRequest $request)
    {
        $this->authorize('create', Term::class);

        return (new TermResource($this->service->create($request->validated(), $request->user())))
            ->response()->setStatusCode(201);
    }

    public function update(UpdateTermRequest $request, Term $term)
    {
        $this->authorize('update', $term);

        return new TermResource($this->service->update($term, $request->validated(), $request->user()));
    }

    public function destroy(Request $request, Term $term)
    {
        $this->authorize('delete', $term);
        $this->service->delete($term, $request->user());

        return response()->noContent();
    }
}