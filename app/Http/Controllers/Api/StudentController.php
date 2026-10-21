<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\AcademicYear;
use App\Models\ClassAssignment;
use App\Models\Student;
use App\Models\StudentGradeLevel;
use App\Services\EnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentController extends Controller {
    private const LOAD = ['sections.gradeLevel', 'yearLevels.gradeLevel'];

    public function index(Request $request) {
        $this->authorize('viewAny', Student::class);
        $user = $request->user();

        $cacheKey = 'students_' . md5(json_encode([
            'user_id' => $user->id,
            'is_admin' => $user->isAdmin(),
            'class_assignment_id' => $request->class_assignment_id,
            'section_id' => $request->section_id,
            'grade_level_id' => $request->grade_level_id,
            'search' => $request->search,
            'page' => $request->page,
            'per_page' => $request->per_page ?? 20,
        ]));

        $result = Cache::remember($cacheKey, 60, function () use ($request, $user) {
            $query = Student::query()->with(self::LOAD);

            if (! $user->isAdmin()) {
                $classIds = $user->teacher?->classAssignments()->pluck('id') ?? collect();
                $query->whereHas('enrollments', fn ($q) => $q->whereIn('class_assignment_id', $classIds));
            }
            if ($request->filled('class_assignment_id')) {
                $query->whereHas('enrollments', fn ($q) => $q->where('class_assignment_id', $request->class_assignment_id));
            }
            if ($request->filled('section_id')) {
                $query->whereHas('enrollments', fn ($q) => $q->where('section_id', $request->section_id));
            }
            if ($request->filled('grade_level_id')) {
                $activeYear = AcademicYear::where('is_active', true)->value('id');
                $query->whereHas('yearLevels', fn ($q) => $q
                    ->where('grade_level_id', $request->grade_level_id)
                    ->when($activeYear, fn ($w) => $w->where('academic_year_id', $activeYear)));
            }
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('student_id', 'like', "%{$search}%");
                });
            }
            $collection = StudentResource::collection($query->paginate($request->per_page ?? 20));
            return $collection->response($request)->getData(true);
        });

        return response()->json($result);
    }

    public function store(StoreStudentRequest $request, EnrollmentService $enrollments) {
        $data = $request->validated();
        $classId = $data['class_assignment_id'] ?? null;
        $gradeId = $request->user()->isAdmin() ? ($data['grade_level_id'] ?? null) : null;
        unset($data['class_assignment_id'], $data['grade_level_id']);

        $ca = $classId ? ClassAssignment::findOrFail($classId) : null;
        if ($ca) {
            $this->authorize('view', $ca); // owner or admin
        }

        $student = DB::transaction(function () use ($data, $ca, $gradeId, $enrollments, $request) {
            $student = Student::create($data);
            if ($ca) {
                // joining a class also sets the student's grade level for that school year
                $enrollments->enroll($ca, [$student->id], $request->user());
            } elseif ($gradeId) {
                $year = AcademicYear::where('is_active', true)->value('id');
                if (! $year) {
                    throw ValidationException::withMessages(['grade_level_id' => ['There is no active academic year yet.']]);
                }
                StudentGradeLevel::create([
                    'student_id' => $student->id,
                    'academic_year_id' => $year,
                    'grade_level_id' => $gradeId,
                ]);
            }
            return $student;
        });

        return new StudentResource($student->load(self::LOAD));
    }

    public function show(Student $student) {
        $this->authorize('view', $student);
        return new StudentResource($student->load(self::LOAD));
    }

    public function update(UpdateStudentRequest $request, Student $student) {
        $student->update($request->validated());
        return new StudentResource($student->load(self::LOAD));
    }

    public function destroy(Student $student) {
        $this->authorize('delete', $student);
        $student->update(['status' => 'archived']);
        $student->delete(); // soft delete — row stays, deleted_at is set
        return response()->noContent();
    }
}