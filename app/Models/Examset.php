<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamSet extends Model
{
    // exam_id and qr_token are set by the service.
    protected $fillable = ['label'];

    public function exam() { return $this->belongsTo(Exam::class); }
    public function questions() { return $this->hasMany(ExamQuestion::class)->orderBy('position')->orderBy('id'); }
}