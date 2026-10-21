<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClassAssignmentRequest;
use App\Http\Requests\UpdateClassAssignmentRequest;
use App\Http\Resources\ClassAssignmentResource;
use App\Models\ClassAssignment;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClassAssignmentController extends Controller
{
    private const WITH = ['section.gradeLevel', 'subject', 'academicYear'];

    public function index(Request $request)
    {
        $user = $request->user();
        $query = ClassAssignment::query()->with(self::WITH);

        if (! $user->isAdmin()) {
            $query->where('teacher_id', $user->teacher?->id);
        }

        return ClassAssignmentResource::collection($query->get());
    }

    public function store(StoreClassAssignmentRequest $request): JsonResponse
    {
        $this->authorize('create', ClassAssignment::class);

        $user = $request->user();
        $teacherId = $user->isAdmin() ? $request->teacher_id : $user->teacher?->id;

        if (! $teacherId) {
            throw ValidationException::withMessages(['teacher_id' => ['A teacher is required.']]);
        }

        $ca = DB::transaction(function () use ($request, $teacherId) {
            $section = Section::firstOrCreate([
                'grade_level_id' => $request->grade_level_id,
                'academic_year_id' => $request->academic_year_id,
                'name' => Str::title(preg_replace('/\s+/', ' ', trim($request->section_name))),
            ]);

            $this->assertNotDuplicate(
                $teacherId, $section->id, $request->subject_id, $request->academic_year_id
            );

            return ClassAssignment::create([
                'teacher_id' => $teacherId,
                'section_id' => $section->id,
                'subject_id' => $request->subject_id,
                'academic_year_id' => $request->academic_year_id,
                'status' => 'active',
            ]);
        });

        return (new ClassAssignmentResource($ca->load(self::WITH)))
            ->response()->setStatusCode(201);
    }

    public function show(ClassAssignment $classAssignment): ClassAssignmentResource
    {
        $this->authorize('view', $classAssignment);

        return new ClassAssignmentResource($classAssignment->load(self::WITH));
    }

    public function update(UpdateClassAssignmentRequest $request, ClassAssignment $classAssignment): ClassAssignmentResource
    {
        $this->authorize('update', $classAssignment);

        $this->assertNotDuplicate(
            $classAssignment->teacher_id,
            $classAssignment->section_id,
            $request->subject_id,
            $classAssignment->academic_year_id,
            $classAssignment->id
        );

        $classAssignment->update($request->validated());

        return new ClassAssignmentResource($classAssignment->load(self::WITH));
    }

    public function destroy(ClassAssignment $classAssignment): Response
    {
        $this->authorize('delete', $classAssignment);

        DB::transaction(function () use ($classAssignment) {
            $classAssignment->update(['status' => 'archived']);
            $classAssignment->delete(); // soft delete keeps attendance history
        });

        return response()->noContent();
    }

    private function assertNotDuplicate(int $teacherId, int $sectionId, int $subjectId, int $yearId, ?int $ignoreId = null): void
    {
        $exists = ClassAssignment::where('teacher_id', $teacherId)
            ->where('section_id', $sectionId)
            ->where('subject_id', $subjectId)
            ->where('academic_year_id', $yearId)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'subject_id' => ['This class already exists for that section, subject, and academic year.'],
            ]);
        }
    }
}