<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkStoreAttendanceRequest;
use App\Http\Requests\ShowAttendanceRequest;
use App\Models\ClassAssignment;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceService $service) {}

    public function index(ShowAttendanceRequest $request): JsonResponse
    {
        $ca = ClassAssignment::findOrFail($request->class_assignment_id);
        $this->authorize('recordAttendance', $ca);

        return response()->json(['data' => [
            'class_assignment_id' => $ca->id,
            'date' => $request->date,
            'students' => $this->service->rosterWithStatus($ca, $request->date),
        ]]);
    }

    public function bulkStore(BulkStoreAttendanceRequest $request): JsonResponse
    {
        $ca = ClassAssignment::findOrFail($request->class_assignment_id);
        $this->authorize('recordAttendance', $ca);

        $count = $this->service->saveBulk(
            $ca,
            $request->date,
            $request->records,
            $request->user()->id
        );

        return response()->json(['message' => 'Attendance saved.', 'saved' => $count]);
    }
}
