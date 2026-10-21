<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamQuestion extends Model
{
    // exam_set_id, position, image_path and image_alt are set by the services.
    protected $fillable = ['body', 'choices', 'correct_choice', 'competency_id'];

    protected $casts = [
        'choices' => 'array',
    ];

    public function set() { return $this->belongsTo(ExamSet::class, 'exam_set_id'); }
    public function competency() { return $this->belongsTo(Competency::class); }
}