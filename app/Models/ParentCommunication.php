<?php

namespace App\Models;

use App\Models\Concerns\HidesArchivedClasses;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A record of contact the teacher already made. It does not send anything. */
class ParentCommunication extends Model
{
    use SoftDeletes, HidesArchivedClasses;

    // author_id is set by the service from the signed-in user.
    protected $fillable = [
        'student_id', 'class_assignment_id', 'contact_date', 'method', 'reason', 'summary', 'follow_up_date',
    ];

    protected $casts = [
        'contact_date' => 'date',
       'follow_up_date' => 'date',
        'follow_up_done_at' => 'date',
    ];

    public function student() { return $this->belongsTo(Student::class)->withTrashed(); }
    public function classAssignment() { return $this->belongsTo(ClassAssignment::class)->withTrashed(); }
    public function author() { return $this->belongsTo(User::class, 'author_id'); }
}
