<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\SubjectRequest;
use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends SetupController
{
    protected function model(): string { return Subject::class; }

    protected function listQuery() { return Subject::orderBy('name'); }

    public function store(SubjectRequest $request)
    {
        return $this->storeItem($request->validated(), $request->user());
    }

    public function update(SubjectRequest $request, Subject $subject)
    {
        return $this->updateItem($subject, $request->validated(), $request->user());
    }

    public function destroy(Request $request, Subject $subject)
    {
        return $this->destroyItem($subject, $request->user());
    }
}