<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $table = 'attendance';

    protected $fillable = [
        'student_id', 'class_assignment_id', 'attendance_date',
        'status', 'remarks', 'recorded_by',
    ];

    protected $casts = ['attendance_date' => 'date:Y-m-d'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classAssignment(): BelongsTo
    {
        return $this->belongsTo(ClassAssignment::class);
    }
}