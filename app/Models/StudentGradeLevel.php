<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentGradeLevel extends Model
{
    protected $fillable = ['student_id', 'academic_year_id', 'grade_level_id'];

    public function student() { return $this->belongsTo(Student::class); }
    public function academicYear() { return $this->belongsTo(AcademicYear::class); }
    public function gradeLevel() { return $this->belongsTo(GradeLevel::class); }
}