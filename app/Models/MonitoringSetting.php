<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonitoringSetting extends Model
{
    protected $fillable = [
        'teacher_id', 'attendance_rate_threshold', 'consecutive_absence_threshold',
        'min_days_for_rate', 'missing_assignments_threshold',
        'guardian_email_enabled', 'notify_on_absent', 'notify_on_late',
    ];

    protected $casts = [
        'attendance_rate_threshold' => 'float',
        'consecutive_absence_threshold' => 'integer',
        'min_days_for_rate' => 'integer',
        'missing_assignments_threshold' => 'integer',
        'guardian_email_enabled' => 'boolean',
        'notify_on_absent' => 'boolean',
        'notify_on_late' => 'boolean',
    ];

    public function teacher() { return $this->belongsTo(Teacher::class); }
}
