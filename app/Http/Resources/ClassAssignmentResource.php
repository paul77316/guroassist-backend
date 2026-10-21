<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,

            // IDs, used to prefill the edit form
            'section_id' => $this->section_id,
            'subject_id' => $this->subject_id,
            'academic_year_id' => $this->academic_year_id,
            'grade_level_id' => $this->section->grade_level_id,

            // Display names (unchanged, so the existing cards keep working)
            'subject' => $this->subject->name,
            'section' => $this->section->name,
            'grade_level' => $this->section->gradeLevel->name,
            'academic_year' => $this->academicYear->name,

            // Counts students currently enrolled in this section for this
            // academic year, so we filter by both.
            // Active students enrolled in this class (enrollments now belong to the class itself).
            'student_count' => \App\Models\Enrollment::where('class_assignment_id', $this->id)
                ->whereHas('student', fn ($q) => $q->where('status', 'active'))
                ->count(),
        ];
    }
}