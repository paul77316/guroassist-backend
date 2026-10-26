<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MobileLoginRequest;
use App\Http\Resources\UserResource;
use App\Models\MobileDevice;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Token login for the installed mobile app. A teacher's first login from a device only registers it;
 * a token is issued once an admin has approved that device.
 */
class MobileAuthController extends Controller
{
    public function login(MobileLoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (! Auth::guard('web')->validate(['email' => $data['email'], 'password' => $data['password']])) {
            throw ValidationException::withMessages(['email' => ['The provided credentials are incorrect.']]);
        }

        $user = User::where('email', $data['email'])->firstOrFail();

        if (! $user->isTeacher() || ! $user->teacher) {
            return response()->json([
                'message' => 'The mobile app is for teacher accounts.',
                'code' => 'mobile_not_available',
            ], 403);
        }

        if ($user->must_change_password) {
            return response()->json([
                'message' => 'Change your initial password on the web before using the mobile app.',
                'code' => 'password_change_required',
            ], 403);
        }

        $device = MobileDevice::firstOrCreate(
            ['user_id' => $user->id, 'device_uuid' => $data['device_uuid']],
            ['name' => $data['device_name'], 'status' => 'pending'],
        );

        if ($device->status === 'revoked') {
            return response()->json([
                'message' => 'This device was removed. Ask an administrator to approve it again.',
                'code' => 'device_revoked',
            ], 403);
        }

        $device->update([
            'last_ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
        ]);

        if ($device->status === 'pending') {
            return response()->json([
                'message' => 'Waiting for an administrator to approve this device.',
                'code' => 'device_pending',
                'data' => ['status' => 'pending', 'device_name' => $device->name],
            ], 202);
        }

        // One live token per device: logging in again replaces the old one.
        $user->tokens()->where('name', $device->tokenName())->delete();
        $expiresAt = now()->addDays(MobileDevice::TOKEN_DAYS);
        $token = $user->createToken($device->tokenName(), [MobileDevice::ABILITY], $expiresAt);
        $device->update(['last_used_at' => now()]);

        return response()->json([
            'token' => $token->plainTextToken,
            'expires_at' => $expiresAt->toIso8601String(),
            'user' => new UserResource($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(null, 204);
    }
}
