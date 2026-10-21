<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\StudentGradeLevel;

/** Admin CRUD for the reference lists: academic years, grade levels, subjects. */
class SetupService
{
    public function create(string $modelClass, array $data, User $user): Model
    {
        return DB::transaction(function () use ($modelClass, $data, $user) {
            $item = $modelClass::create($data);
            $this->afterSave($item);
            AuditLogger::log($user, $item, 'created', null, $this->values($item));

            return $item;
        });
    }

    public function update(Model $item, array $data, User $user): Model
    {
        $old = $this->values($item);

        return DB::transaction(function () use ($item, $data, $user, $old) {
            $item->update($data);
            $this->afterSave($item);
            AuditLogger::log($user, $item, 'updated', $old, $this->values($item->refresh()));

            return $item;
        });
    }

    public function delete(Model $item, User $user): void
    {
        $used = collect($this->usage($item))->filter()->map(fn ($n, $label) => "$n $label")->values();

        if ($used->isNotEmpty()) {
            throw ValidationException::withMessages([
                'item' => ["Can't delete: it is used by " . $used->join(', ') . '. Edit it instead.'],
            ]);
        }

        DB::transaction(function () use ($item, $user) {
            AuditLogger::log($user, $item, 'deleted', $this->values($item), null);
            $item->delete();
        });
    }

    /** What depends on this item (archived classes count: their history is kept). */
    public function usage(Model $item): array
    {
        return match (true) {
            $item instanceof AcademicYear => [
                'sections' => $item->sections()->count(),
                'classes' => $item->classAssignments()->withTrashed()->count(),
                'terms' => $item->terms()->count(),
            ],
            $item instanceof GradeLevel => [
                'sections' => $item->sections()->count(),
                'student records' => StudentGradeLevel::where('grade_level_id', $item->id)->count(),
            ],
            $item instanceof Subject => ['classes' => $item->classAssignments()->withTrashed()->count()],
            default => [],
        };
    }

    /** The app expects a single active academic year. */
    private function afterSave(Model $item): void
    {
        if ($item instanceof AcademicYear && $item->is_active) {
            AcademicYear::where('id', '!=', $item->id)->where('is_active', true)->update(['is_active' => false]);
        }
    }

    private function values(Model $item): array
    {
        return Arr::except($item->getAttributes(), ['created_at', 'updated_at']);
    }
}