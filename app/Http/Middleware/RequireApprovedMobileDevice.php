<?php

namespace App\Http\Middleware;

use App\Models\MobileDevice;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mobile routes need a real device token (never a web session) whose device an admin approved.
 * A revoked device loses its tokens at once, and any token left over is refused here too.
 */
class RequireApprovedMobileDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if (! $token instanceof PersonalAccessToken || ! $token->can(MobileDevice::ABILITY)) {
            return response()->json([
                'message' => 'This feature is only available in the GuroAssist mobile app.',
                'code' => 'mobile_token_required',
            ], 403);
        }

        $device = MobileDevice::query()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereKey(MobileDevice::idFromTokenName($token->name))
            ->first();

        if (! $device) {
            return response()->json([
                'message' => 'This device is not approved.',
                'code' => 'device_not_approved',
            ], 403);
        }

        if (! $device->last_used_at || $device->last_used_at->lt(now()->subMinutes(5))) {
            $device->update(['last_used_at' => now(), 'last_ip' => $request->ip()]);
        }

        return $next($request);
    }
}
