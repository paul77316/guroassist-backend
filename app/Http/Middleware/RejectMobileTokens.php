<?php

namespace App\Http\Middleware;

use App\Models\MobileDevice;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/** Keeps a mobile device token out of every web route. */
class RejectMobileTokens
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken && $token->can(MobileDevice::ABILITY)) {
            return response()->json([
                'message' => 'The mobile app cannot use this feature.',
                'code' => 'web_only',
            ], 403);
        }

        return $next($request);
    }
}
