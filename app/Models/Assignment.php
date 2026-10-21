<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assignment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'class_assignment_id', 'title', 'description', 'assigned_date', 'due_date', 'created_by',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'due_date' => 'date',
    ];

    // withTrashed: an archived class is soft-deleted, but its assignments must stay readable.
    public function classAssignment() { return $this->belongsTo(ClassAssignment::class)->withTrashed(); }
    public function submissions() { return $this->hasMany(AssignmentSubmission::class); }
}
