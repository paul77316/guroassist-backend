<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttendanceCorrectionRequest;
use App\Services\AttendanceCorrectionService;
use Illuminate\Http\JsonResponse;

class AttendanceCorrectionController extends Controller
{
    public function __construct(private AttendanceCorrectionService $service) {}

    public function store(StoreAttendanceCorrectionRequest $request): JsonResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $result = $this->service->correct($request->user(), $request->validated());

        if ($result['stale']) {
            return response()->json([
                'message' => 'This mark changed since you opened it. Reload and try again.',
                'code' => 'stale_version',
                'current' => $result['current'],
            ], 409);
        }

        return response()->json(['data' => $result['current']]);
    }
}
