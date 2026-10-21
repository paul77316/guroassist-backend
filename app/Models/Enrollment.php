<?php 
namespace App\Models; 
use Illuminate\Database\Eloquent\Model; 
class Enrollment extends Model { 
    protected $fillable = ['student_id', 'class_assignment_id', 'section_id', 'academic_year_id'];

    public function student() { 
        return $this->belongsTo(Student::class); 
    } 

    public function section() { 
        return $this->belongsTo(Section::class); 
    } 

    public function academicYear() { 
        return $this->belongsTo(AcademicYear::class); 
    } 
    public function classAssignment() { return $this->belongsTo(ClassAssignment::class); }
}