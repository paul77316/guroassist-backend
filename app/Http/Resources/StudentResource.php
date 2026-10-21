<?php
namespace App\Http\Resources;

use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource {
    private static function activeYearId(): ?int {
        static $id = false;
        if ($id === false) {
            $id = AcademicYear::where('is_active', true)->value('id');
        }
        return $id;
    }

    public function toArray(Request $request): array {
        // Grade level for the active school year (falls back to the most recent record)
        $level = null;
        if ($this->relationLoaded('yearLevels')) {
            $level = $this->yearLevels->firstWhere('academic_year_id', self::activeYearId())
                ?? $this->yearLevels->sortByDesc('academic_year_id')->first();
        }

        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'sex' => $this->sex,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'contact_number' => $this->contact_number,
            'guardian_name' => $this->guardian_name,
            'guardian_contact' => $this->guardian_contact,
            'guardian_email' => $this->guardian_email,
            'status' => $this->status,
            'grade_level_id' => $level?->grade_level_id,
            'grade_level' => $level?->gradeLevel?->name,
            'sections' => $this->whenLoaded('sections', fn () => $this->sections->unique('id')->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'grade_level_id' => $s->grade_level_id,
                'grade_level' => $s->gradeLevel?->name,
            ])->values()),
        ];
    }
}