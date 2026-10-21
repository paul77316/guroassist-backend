<?php 
namespace App\Models; 
use Illuminate\Database\Eloquent\Factories\HasFactory; 
use Illuminate\Database\Eloquent\Model; 

class Teacher extends Model { 
    use HasFactory; protected $fillable = [ 'user_id', 'employee_id', 'contact_number', ]; 
    public function user() { 
        return $this->belongsTo(User::class); 
    } 

    public function classAssignments() { 
        return $this->hasMany(ClassAssignment::class); 
    } 
}