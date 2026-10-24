<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResolveAttendanceSyncOperationRequest;
use App\Http\Requests\StoreAttendanceSyncBatchRequest;
use App\Models\AttendanceSyncOperation;
use App\Models\ClassAssignment;
use App\Services\AttendanceSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AttendanceSyncController extends Controller
{
    public function __construct(private AttendanceSyncService $service) {}

    /** Always 200 for a well-formed batch: read each operation's own outcome. */
    public function store(StoreAttendanceSyncBatchRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->service->process($request->user(), $request->validated('operations')),
        ]);
    }

    public function resolve(ResolveAttendanceSyncOperationRequest $request, string $operation_id): JsonResponse
    {
        $operation = AttendanceSyncOperation::where('operation_id', $operation_id)->firstOrFail();
        $ca = ClassAssignment::withTrashed()->findOrFail($operation->class_assignment_id);

        $this->authorize('recordAttendance', $ca);

        if ($ca->trashed() || $ca->status !== 'active') {
            throw ValidationException::withMessages(['operation' => ['This class is archived.']]);
        }

        return response()->json([
            'data' => $this->service->resolve($request->user(), $operation, $request->validated('resolution')),
        ]);
    }

    /**
     * Server half of the new-session gate. It cannot see changes that never left the device, so the
     * client must also check its own queue and only then treat the device as ready.
     */
    public function readiness(Request $request): JsonResponse
    {
        $count = $this->service->unresolvedConflictCount($request->user());

        return response()->json(['data' => [
            'unresolved_conflicts' => $count,
            'server_ready' => $count === 0,
        ]]);
    }
}
