<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MobileDevice extends Model
{
    /** The only ability a mobile token carries. Web sessions never have it. */
    public const ABILITY = 'attendance:sync';

    public const MAX_APPROVED_PER_USER = 2;

    public const TOKEN_DAYS = 30;

    protected $fillable = [
        'user_id', 'device_uuid', 'name', 'status', 'approved_by', 'approved_at',
        'revoked_at', 'last_used_at', 'last_ip', 'user_agent',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'revoked_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tokenName(): string
    {
        return 'mobile-device:'.$this->id;
    }

    public static function idFromTokenName(?string $name): int
    {
        return preg_match('/^mobile-device:(\d+)$/', (string) $name, $m) ? (int) $m[1] : 0;
    }
}
