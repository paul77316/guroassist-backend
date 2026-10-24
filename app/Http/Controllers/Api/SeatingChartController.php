<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveSeatingChartRequest;
use App\Models\ClassAssignment;
use App\Models\ClassroomSeatingLayout;
use App\Services\AttendanceService;
use App\Services\AuditLogger;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SeatingChartController extends Controller
{
    public function __construct(private AttendanceService $attendance) {}

    public function show(ClassAssignment $class_assignment): JsonResponse
    {
        $this->authorize('view', $class_assignment);

        return response()->json(['data' => $this->payload($class_assignment)]);
    }

    public function update(SaveSeatingChartRequest $request, ClassAssignment $class_assignment): JsonResponse
    {
        $this->authorize('update', $class_assignment);

        if ($class_assignment->status !== 'active') {
            throw ValidationException::withMessages(['class_assignment' => ['This class is archived.']]);
        }

        $data = $request->validated();
        $cells = array_map(fn ($id) => $id === null ? null : (int) $id, array_values($data['cells']));
        $placed = array_values(array_filter($cells, fn ($id) => $id !== null));

        if (count($cells) !== (int) $data['rows'] * (int) $data['cols']) {
            throw ValidationException::withMessages(['cells' => ['The seat list does not match rows x columns.']]);
        }
        if (count($placed) !== count(array_unique($placed))) {
            throw ValidationException::withMessages(['cells' => ['A student can only have one seat.']]);
        }
        if (array_diff($placed, $this->attendance->roster($class_assignment)->pluck('id')->all())) {
            throw ValidationException::withMessages(['cells' => ['Some students are not enrolled in this class.']]);
        }

        try {
            $stale = DB::transaction(function () use ($request, $class_assignment, $data, $cells) {
                $layout = ClassroomSeatingLayout::where('class_assignment_id', $class_assignment->id)->lockForUpdate()->first();
                $current = $layout ? $layout->version : 0;
                if ($current !== (int) $data['version']) {
                    return $current;
                }

                $old = $layout ? ['rows' => $layout->rows, 'cols' => $layout->cols, 'cells' => $layout->cells, 'version' => $current] : null;
                $values = ['rows' => (int) $data['rows'], 'cols' => (int) $data['cols'], 'cells' => $cells, 'updated_by' => $request->user()->id];

                if ($layout) {
                    $layout->update($values + ['version' => $current + 1]);
                } else {
                    $layout = ClassroomSeatingLayout::create($values + ['class_assignment_id' => $class_assignment->id, 'version' => 1]);
                }

                AuditLogger::log($request->user(), $layout, 'seating.saved', $old,
                    ['rows' => $layout->rows, 'cols' => $layout->cols, 'cells' => $layout->cells, 'version' => $layout->version]);

                return null;
            });
        } catch (UniqueConstraintViolationException) {
            $stale = 1;
        }

        if ($stale !== null) {
            return response()->json([
                'message' => 'The seating chart changed since you loaded it. Reload and try again.',
                'current_version' => ClassroomSeatingLayout::where('class_assignment_id', $class_assignment->id)->value('version') ?? 0,
            ], 409);
        }

        return response()->json(['data' => $this->payload($class_assignment)]);
    }

    /** @return array<string, mixed> */
    private function payload(ClassAssignment $ca): array
    {
        $layout = ClassroomSeatingLayout::where('class_assignment_id', $ca->id)->first();
        $rows = $layout->rows ?? 5;
        $cols = $layout->cols ?? 5;

        return [
            'class_assignment_id' => $ca->id,
            'version' => $layout->version ?? 0,
            'rows' => $rows,
            'cols' => $cols,
            'cells' => $layout->cells ?? array_fill(0, $rows * $cols, null),
            'students' => $this->attendance->roster($ca)->map(fn ($s) => [
                'student_id' => $s->id, 'full_name' => $s->full_name,
            ])->values(),
        ];
    }
}
