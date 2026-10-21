<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function academicYear() { return $this->belongsTo(AcademicYear::class); }
}