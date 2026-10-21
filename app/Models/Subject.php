<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Subject extends Model {
    protected $fillable = ['name', 'code'];

    protected static function booted()
    {
        static::saved(function () {
            Cache::forget('subjects');
        });
        static::deleted(function () {
            Cache::forget('subjects');
        });
    }

    public function classAssignments() {
        return $this->hasMany(ClassAssignment::class);
    }
}