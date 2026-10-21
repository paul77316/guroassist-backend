<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignmentSubmission extends Model
{
    protected $fillable = [
        'assignment_id', 'student_id', 'status', 'submitted_date', 'feedback', 'recorded_by',
    ];

    protected $casts = ['submitted_date' => 'date'];

    // The teacher's private note never appears in a serialized model; only the grid reads it explicitly.
    protected $hidden = ['feedback'];

    public function assignment() { return $this->belongsTo(Assignment::class); }
    public function student() { return $this->belongsTo(Student::class); }
}
