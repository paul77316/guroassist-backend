<?php

namespace App\Models;

use Database\Factories\ExamScanBatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamScanBatch extends Model
{
    /** @use HasFactory<ExamScanBatchFactory> */
    use HasFactory;

    protected $fillable = [
        'exam_set_id', 'created_by', 'client_batch_id', 'payload_hash', 'sheet_count',
        'reviewed_mark_count', 'duplicate_check_confirmed',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_mark_count' => 'integer',
            'duplicate_check_confirmed' => 'boolean',
        ];
    }

    public function set(): BelongsTo
    {
        return $this->belongsTo(ExamSet::class, 'exam_set_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questionCounts(): HasMany
    {
        return $this->hasMany(ExamScanQuestionCount::class);
    }
}
