<?php 
namespace App\Models; 
use Illuminate\Database\Eloquent\Model; 

class AcademicYear extends Model { 
    protected $fillable = ['name', 'start_date', 'end_date', 'is_active']; 
    protected $casts = [ 
        'start_date' => 'date', 
        'end_date' => 'date', 
        'is_active' => 'boolean', 
    ]; 
    public function sections() { 
        return $this->hasMany(Section::class); 
    } 
    public function terms() { return $this->hasMany(Term::class)->orderBy('sort_order'); }
    public function classAssignments() { return $this->hasMany(ClassAssignment::class); }
}