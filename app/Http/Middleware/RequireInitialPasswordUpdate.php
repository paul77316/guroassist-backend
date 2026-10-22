<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireInitialPasswordUpdate
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->must_change_password) {
            return $next($request);
        }

        $expired = ! $user->initial_password_expires_at || $user->initial_password_expires_at->isPast();

        return response()->json([
            'message' => $expired
                ? 'Your initial password has expired. Contact an administrator to reset it.'
                : 'Change your initial password before using GuroAssist.',
            'code' => $expired ? 'initial_password_expired' : 'password_change_required',
        ], 403);
    }
}
