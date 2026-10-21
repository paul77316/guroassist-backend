<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false; // created_at is set by the database default

    protected $fillable = ['user_id', 'auditable_type', 'auditable_id', 'event', 'old_values', 'new_values', 'ip_address'];

    protected $casts = ['old_values' => 'array', 'new_values' => 'array', 'created_at' => 'datetime'];

    public function auditable() { return $this->morphTo(); }
    public function user() { return $this->belongsTo(User::class); }
}