<?php

namespace App\Models;

use Database\Factories\ExamScanQuestionCountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamScanQuestionCount extends Model
{
    /** @use HasFactory<ExamScanQuestionCountFactory> */
    use HasFactory;

    protected $fillable = [
        'exam_scan_batch_id', 'exam_question_id', 'choice_counts', 'blank_count', 'multiple_count',
    ];

    protected function casts(): array
    {
        return [
            'choice_counts' => 'array',
            'blank_count' => 'integer',
            'multiple_count' => 'integer',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ExamScanBatch::class, 'exam_scan_batch_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ExamQuestion::class, 'exam_question_id');
    }
}
