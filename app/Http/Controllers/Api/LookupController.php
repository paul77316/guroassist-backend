<?php

namespace App\Http\Controllers\Api;
use App\Models\Term;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class LookupController extends Controller
{
    public function gradeLevels(): JsonResponse
    {
        return response()->json([
            'data' => Cache::remember('grade_levels', 3600, function () {
                return GradeLevel::orderBy('order')->orderBy('id')->get();
            })
        ]);
    }

    public function subjects(): JsonResponse
    {
        return response()->json([
            'data' => Cache::remember('subjects', 3600, function () {
                return Subject::orderBy('name')->get();
            })
        ]);
    }

    public function academicYears(): JsonResponse
    {
        return response()->json([
            'data' => Cache::remember('academic_years', 3600, function () {
                return AcademicYear::orderByDesc('id')->get();
            })
        ]);
    }
    public function terms(Request $request)
    {
        $cacheKey = 'terms_' . ($request->academic_year_id ?? 'all');

        $terms = Cache::remember($cacheKey, 3600, function () use ($request) {
            return Term::query()
                ->when($request->academic_year_id, fn ($q, $id) => $q->where('academic_year_id', $id))
                ->orderBy('academic_year_id')
                ->orderBy('sort_order')
                ->get();
        });

        return response()->json(['data' => $terms]);
    }

}
