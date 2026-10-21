<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AcademicYear extends Model {
    protected $fillable = ['name', 'start_date', 'end_date', 'is_active'];
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::saved(function () {
            Cache::forget('academic_years');
        });
        static::deleted(function () {
            Cache::forget('academic_years');
        });
    }

    public function sections() {
        return $this->hasMany(Section::class);
    }
    public function terms() { return $this->hasMany(Term::class)->orderBy('sort_order'); }
    public function classAssignments() { return $this->hasMany(ClassAssignment::class); }
}