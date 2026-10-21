<?php
use App\Models\{AuditLog, ClassAssignment, GradeLevel, Section, Student};
$ids = GradeLevel::where('name', 'like', 'ZZ%')->pluck('id');
$sectionIds = Section::whereIn('grade_level_id', $ids)->pluck('id');
ClassAssignment::withTrashed()->whereIn('section_id', $sectionIds)->get()->each->forceDelete();
Section::whereIn('id', $sectionIds)->delete();
GradeLevel::whereIn('id', $ids)->delete();
Student::withTrashed()->where('student_id', 'like', 'TEST-%')->get()->each->forceDelete();
AuditLog::query()->delete();
@unlink(base_path('scratch/level_test.csv'));
echo "Left over (expect 0, 0, 0): ",
    GradeLevel::where('name', 'like', 'ZZ%')->count(), ", ",
    Section::where('name', 'like', 'ZZ%')->count(), ", ",
    Student::withTrashed()->where('student_id', 'like', 'TEST-%')->count(), "\n";
