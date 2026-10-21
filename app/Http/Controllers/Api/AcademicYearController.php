<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\AcademicYearRequest;
use App\Models\AcademicYear;
use Illuminate\Http\Request;

class AcademicYearController extends SetupController
{
    protected function model(): string { return AcademicYear::class; }

    protected function listQuery() { return AcademicYear::orderByDesc('start_date'); }

    public function store(AcademicYearRequest $request)
    {
        return $this->storeItem($request->validated(), $request->user());
    }

    public function update(AcademicYearRequest $request, AcademicYear $academicYear)
    {
        return $this->updateItem($academicYear, $request->validated(), $request->user());
    }

    public function destroy(Request $request, AcademicYear $academicYear)
    {
        return $this->destroyItem($academicYear, $request->user());
    }
}