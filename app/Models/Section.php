<?php 
namespace App\Models; 
use Illuminate\Database\Eloquent\Model; 

class Section extends Model { 
    protected $fillable = ['name', 'grade_level_id', 'academic_year_id', 'adviser_id']; 

    public function gradeLevel() { 
        return $this->belongsTo(GradeLevel::class); 
    } 

    public function academicYear() { 
        return $this->belongsTo(AcademicYear::class); 
    } 

    public function adviser() { 
        return $this->belongsTo(Teacher::class, 'adviser_id'); 
    } 

    public function classAssignments() { 
        return $this->hasMany(ClassAssignment::class); 
    } 

    public function enrollments() { 
        return $this->hasMany(Enrollment::class); 
    } 

    public function students() { 
        return $this->belongsToMany(Student::class, 'enrollments') 
            ->withPivot('academic_year_id') 
            ->withTimestamps(); 
    } 
}