<?php

namespace App\Models;

use App\Models\Concerns\HidesArchivedClasses;
use App\Support\InterventionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Intervention extends Model
{
    use SoftDeletes, HidesArchivedClasses;

    // author_id, status, follow_up_date and outcome are set by the service (status rules live there),
    // so a client cannot forge them through mass assignment.
    protected $fillable = [
        'student_id', 'class_assignment_id', 'concern_type', 'concern',
        'intervention_type', 'intervention_date', 'notes',
    ];

    protected $casts = [
        'intervention_date' => 'date',
        'follow_up_date' => 'date',
    ];

    public function student() { return $this->belongsTo(Student::class)->withTrashed(); }
    public function classAssignment() { return $this->belongsTo(ClassAssignment::class)->withTrashed(); }
    public function author() { return $this->belongsTo(User::class, 'author_id'); }
    public function followups() { return $this->hasMany(InterventionFollowup::class)->orderBy('followup_date')->orderBy('id'); }

    /**
     * Follow-up due: follow_up_date <= today (school timezone, passed in by the caller),
     * not Completed. Soft-deleted rows are excluded by SoftDeletes. Calculated, never stored.
     * The same scope serves the follow-ups API and the later dashboard and notifications.
     */
    public function scopeDue(Builder $query, string $today): Builder
    {
        return $query
            ->whereNotNull($this->qualifyColumn('follow_up_date'))
            ->whereDate($this->qualifyColumn('follow_up_date'), '<=', $today)
            ->where($this->qualifyColumn('status'), '!=', InterventionStatus::COMPLETED);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('status'), '!=', InterventionStatus::COMPLETED);
    }
}
