<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Term extends Model
{
    public const UPCOMING = 'upcoming';
    public const ACTIVE = 'active';
    public const COMPLETED = 'completed';

    protected $fillable = ['academic_year_id', 'name', 'sort_order', 'start_date', 'end_date', 'status'];

    protected $casts = [
        'sort_order' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected static function booted()
    {
        static::saved(function ($term) {
            Cache::forget("terms_{$term->academic_year_id}");
            Cache::forget('terms_all');
        });
        static::deleted(function ($term) {
            Cache::forget("terms_{$term->academic_year_id}");
            Cache::forget('terms_all');
        });
    }

    public function academicYear() { return $this->belongsTo(AcademicYear::class); }
}