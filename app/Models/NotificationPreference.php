<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A teacher's in-app reminder switches. Holds no student data. */
class NotificationPreference extends Model
{
    protected $fillable = ['teacher_id', 'in_app_enabled', 'include_students_to_review'];

    protected $casts = [
        'in_app_enabled' => 'boolean',
        'include_students_to_review' => 'boolean',
    ];

    public function teacher() { return $this->belongsTo(Teacher::class); }
}
