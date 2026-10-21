<?php

namespace App\Http\Controllers\Api;
use App\Models\Term;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;

class LookupController extends Controller
{
    public function gradeLevels(): JsonResponse
    {
        return response()->json(['data' => GradeLevel::orderBy('order')->orderBy('id')->get()]);
    }

    public function subjects(): JsonResponse
    {
        return response()->json(['data' => Subject::orderBy('name')->get()]);
    }

    public function academicYears(): JsonResponse
    {
        return response()->json(['data' => AcademicYear::orderByDesc('id')->get()]);
    }
    public function terms(Request $request)
    {
        $terms = Term::query()
            ->when($request->academic_year_id, fn ($q, $id) => $q->where('academic_year_id', $id))
            ->orderBy('academic_year_id')
            ->orderBy('sort_order')
            ->get();

        return response()->json(['data' => $terms]);
    }
    
}
