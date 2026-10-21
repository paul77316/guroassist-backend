<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\GradeLevelRequest;
use App\Models\GradeLevel;
use Illuminate\Http\Request;

class GradeLevelController extends SetupController
{
    protected function model(): string { return GradeLevel::class; }

    protected function listQuery() { return GradeLevel::orderBy('order')->orderBy('id'); }

    public function store(GradeLevelRequest $request)
    {
        return $this->storeItem($request->validated(), $request->user());
    }

    public function update(GradeLevelRequest $request, GradeLevel $gradeLevel)
    {
        return $this->updateItem($gradeLevel, $request->validated(), $request->user());
    }

    public function destroy(Request $request, GradeLevel $gradeLevel)
    {
        return $this->destroyItem($gradeLevel, $request->user());
    }
}