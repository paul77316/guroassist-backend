<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceSyncOperation extends Model
{
    protected $fillable = [
        'operation_id', 'user_id', 'class_assignment_id', 'student_id', 'attendance_date',
        'status', 'remarks', 'base_version', 'payload_hash', 'outcome', 'server_version',
        'resolution', 'resolved_by', 'resolved_at',
    ];

    protected $casts = [
        'attendance_date' => 'date:Y-m-d',
        'base_version' => 'integer',
        'server_version' => 'integer',
        'resolved_at' => 'datetime',
    ];

    public function classAssignment()
    {
        return $this->belongsTo(ClassAssignment::class)->withTrashed();
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
