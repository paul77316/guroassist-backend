<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MobileDevice;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MobileDeviceAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $filters = $request->validate(['status' => ['sometimes', 'in:pending,approved,revoked']]);

        $devices = MobileDevice::query()
            ->with('user:id,name,email')
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->orderBy('status')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        return response()->json(['data' => $devices->map(fn (MobileDevice $d) => $this->present($d))->values()]);
    }

    public function approve(Request $request, MobileDevice $device): JsonResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        if (in_array($device->status, ['pending', 'revoked'], true)) {
            $approved = MobileDevice::where('user_id', $device->user_id)->where('status', 'approved')->count();
            if ($approved >= MobileDevice::MAX_APPROVED_PER_USER) {
                throw ValidationException::withMessages(['device' => ['This teacher already has '.MobileDevice::MAX_APPROVED_PER_USER.' approved devices. Remove one first.']]);
            }

            $oldStatus = $device->status;
            $device->update([
                'status' => 'approved',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'revoked_at' => null,
            ]);
            AuditLogger::log($request->user(), $device, 'mobile_device.approved', ['status' => $oldStatus], ['status' => 'approved']);
        }

        return response()->json(['data' => $this->present($device->load('user:id,name,email'))]);
    }

    public function revoke(Request $request, MobileDevice $device): JsonResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        if ($device->status !== 'revoked') {
            $old = $device->status;
            $device->update(['status' => 'revoked', 'revoked_at' => now()]);
            $device->user->tokens()->where('name', $device->tokenName())->delete();
            AuditLogger::log($request->user(), $device, 'mobile_device.revoked', ['status' => $old], ['status' => 'revoked']);
        }

        return response()->json(['data' => $this->present($device->load('user:id,name,email'))]);
    }

    /** @return array<string, mixed> */
    private function present(MobileDevice $d): array
    {
        return [
            'id' => $d->id,
            'status' => $d->status,
            'device_name' => $d->name,
            'teacher' => ['id' => $d->user?->id, 'name' => $d->user?->name, 'email' => $d->user?->email],
            'last_ip' => $d->last_ip,
            'user_agent' => $d->user_agent,
            'registered_at' => $d->created_at?->toIso8601String(),
            'approved_at' => $d->approved_at?->toIso8601String(),
            'revoked_at' => $d->revoked_at?->toIso8601String(),
            'last_used_at' => $d->last_used_at?->toIso8601String(),
        ];
    }
}
