<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceNotification extends Model
{
    protected $fillable = [
        'student_id', 'class_assignment_id', 'attendance_date', 'status', 'state', 'error', 'sent_at',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'sent_at' => 'datetime',
    ];

    public function student() { return $this->belongsTo(Student::class); }
    public function classAssignment() { return $this->belongsTo(ClassAssignment::class); }
}
