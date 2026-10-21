<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class GradeLevel extends Model {
    protected $fillable = ['name', 'order'];

    protected static function booted()
    {
        static::saved(function () {
            Cache::forget('grade_levels');
        });
        static::deleted(function () {
            Cache::forget('grade_levels');
        });
    }

    public function sections() {
        return $this->hasMany(Section::class);
    }
}