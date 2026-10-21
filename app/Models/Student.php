<?php 
namespace App\Models; 
use Illuminate\Database\Eloquent\Factories\HasFactory; 
use Illuminate\Database\Eloquent\Model; 
use Illuminate\Database\Eloquent\SoftDeletes; 

class Student extends Model { 
    use HasFactory, SoftDeletes; 

    protected $fillable = [ 
        'student_id', 
        'first_name', 
        'middle_name', 
        'last_name', 
        'sex', 
        'date_of_birth', 
        'contact_number', 
        'guardian_name', 
        'guardian_contact', 
        'guardian_email',
        'status', 

    ]; 

    protected $casts = [ 
        'date_of_birth' => 'date', 
    ]; 

    public function enrollments() { 
        return $this->hasMany(Enrollment::class); 
    } 
    public function yearLevels() { return $this->hasMany(StudentGradeLevel::class); }
    public function notes() { return $this->hasMany(StudentNote::class); }
    public function interventions() { return $this->hasMany(Intervention::class); }
    public function parentCommunications() { return $this->hasMany(ParentCommunication::class); }
    public function sections() { 
        return $this->belongsToMany(Section::class, 'enrollments') 
            ->withPivot('academic_year_id') 
            ->withTimestamps(); 
    } 

    // Convenience accessor: $student->full_name instead of concatenating manually everywhere 
    public function getFullNameAttribute(): string { 
        $middle = $this->middle_name ? " {$this->middle_name} " : ' '; 
        return trim("{$this->first_name}{$middle}{$this->last_name}"); 
    } 
}