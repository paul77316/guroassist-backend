<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A teacher's own competency label. Questions are tagged with one; class results are grouped by it. */
class Competency extends Model
{
    // owner_id is set by the service from the signed-in user.
    protected $fillable = ['name', 'subject_id'];

    public function owner() { return $this->belongsTo(User::class, 'owner_id'); }
    public function subject() { return $this->belongsTo(Subject::class); }
    public function questions() { return $this->hasMany(ExamQuestion::class); }
}