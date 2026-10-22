<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeInitialPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();
        if (! Auth::attempt($credentials, remember: true)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }
        $user = $request->user();
        if ($user->must_change_password && (! $user->initial_password_expires_at || $user->initial_password_expires_at->isPast())) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => ['Your initial password has expired. Contact an administrator to reset it.'],
            ]);
        }
        $request->session()->regenerate();

        return response()->json([
            'user' => new UserResource($request->user()),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => new UserResource($request->user()),
        ]);
    }

    public function changeInitialPassword(ChangeInitialPasswordRequest $request)
    {
        $user = $request->user();
        $user->update([
            'password' => $request->validated()['password'],
            'must_change_password' => false,
            'initial_password_expires_at' => null,
        ]);
        $request->session()->regenerate();

        return response()->json([
            'user' => new UserResource($user->refresh()),
        ]);
    }
}
