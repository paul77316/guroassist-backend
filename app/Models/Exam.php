<?php

namespace App\Models;

use App\Models\Concerns\HidesArchivedClasses;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A multiple-choice exam for one class. Holds no student data. */
class Exam extends Model
{
    use SoftDeletes, HidesArchivedClasses;

    // author_id and class_assignment_id are set by the service.
    protected $fillable = ['title', 'exam_date', 'choice_count'];

    protected $casts = [
        'exam_date' => 'date',
        'choice_count' => 'integer',
    ];

    public function author() { return $this->belongsTo(User::class, 'author_id'); }
    public function classAssignment() { return $this->belongsTo(ClassAssignment::class)->withTrashed(); }
    public function sets() { return $this->hasMany(ExamSet::class); }
    public function questions() { return $this->hasManyThrough(ExamQuestion::class, ExamSet::class, 'exam_id', 'exam_set_id'); }
}