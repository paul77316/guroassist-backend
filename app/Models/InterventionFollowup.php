<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One history entry per event on an intervention (follow-up, reschedule, completion, reopen).
 * Append-only: no soft deletes, no edits. Created only by the intervention service.
 */
class InterventionFollowup extends Model
{
    protected $fillable = [
        'intervention_id', 'author_id', 'entry_type', 'followup_date',
        'from_status', 'to_status', 'progress', 'notes', 'next_follow_up_date', 'outcome',
    ];

    protected $casts = [
        'followup_date' => 'date',
        'next_follow_up_date' => 'date',
    ];

    public const TYPE_FOLLOWUP = 'followup';
    public const TYPE_RESCHEDULED = 'rescheduled';
    public const TYPE_COMPLETED = 'completed';
    public const TYPE_REOPENED = 'reopened';

    public function intervention() { return $this->belongsTo(Intervention::class)->withTrashed(); }
    public function author() { return $this->belongsTo(User::class, 'author_id'); }
}
