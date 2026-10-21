<?php

namespace App\Models;

use App\Models\Concerns\HidesArchivedClasses;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentNote extends Model
{
    use SoftDeletes, HidesArchivedClasses;

    // author_id is set by the service from the signed-in user, never from the request.
    protected $fillable = ['student_id', 'class_assignment_id', 'body'];

    // withTrashed: an archived student or class must not break reading the record.
    public function student() { return $this->belongsTo(Student::class)->withTrashed(); }
    public function classAssignment() { return $this->belongsTo(ClassAssignment::class)->withTrashed(); }
    public function author() { return $this->belongsTo(User::class, 'author_id'); }
}
