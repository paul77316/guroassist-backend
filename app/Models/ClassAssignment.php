<?php 

namespace App\Models; 
use Illuminate\Database\Eloquent\Model; 
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassAssignment extends Model { 
    use SoftDeletes;
    protected $fillable = ['teacher_id', 'subject_id', 'section_id', 'academic_year_id', 'status']; 
    public function teacher() { return $this->belongsTo(Teacher::class); } 
    public function subject() { return $this->belongsTo(Subject::class); } 
    public function section() { return $this->belongsTo(Section::class); } 
    public function academicYear() { return $this->belongsTo(AcademicYear::class); } 
    public function enrollments() { return $this->hasMany(Enrollment::class); }
}