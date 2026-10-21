<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassAssignment;
use App\Models\Enrollment;
use App\Models\Student;
use App\Services\AttendanceSummaryService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceSummaryController extends Controller
{
    /** Every student in a class, with counts, rate and review reasons. */
    public function show(Request $request, AttendanceSummaryService $service)
    {
        $data = $request->validate($this->rules($request));

        $ca = ClassAssignment::findOrFail($data['class_assignment_id']);
        $this->authorize('view', $ca); // owner or admin

        [$from, $to] = $this->period($data);
        if ($this->tooLong($from, $to)) {
            return $this->tooLongResponse();
        }

        return response()->json(['data' => $service->summary($ca, $from, $to)]);
    }

    /** One student in a class, with the date of every attendance mark. */
    public function student(Request $request, AttendanceSummaryService $service)
    {
        $data = $request->validate($this->rules($request) + [
            'student_id' => ['required', 'integer', 'exists:students,id'],
        ]);

        $ca = ClassAssignment::findOrFail($data['class_assignment_id']);
        $this->authorize('view', $ca); // owner or admin

        $student = Student::findOrFail($data['student_id']);
        abort_unless(
            Enrollment::where('class_assignment_id', $ca->id)->where('student_id', $student->id)->exists(),
            404
        );

        [$from, $to] = $this->period($data);
        if ($this->tooLong($from, $to)) {
            return $this->tooLongResponse();
        }

        return response()->json(['data' => $service->studentHistory($ca, $student, $from, $to)]);
    }

    private function rules(Request $request): array
    {
        return [
            'class_assignment_id' => ['required', 'integer', 'exists:class_assignments,id'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => array_filter([
                'nullable', 'date_format:Y-m-d',
                $request->filled('from') ? 'after_or_equal:from' : null,
            ]),
        ];
    }

    /** Defaults to the last 30 days, using the school's timezone for "today". */
    private function period(array $data): array
    {
        $to = $data['to'] ?? Carbon::now(config('monitoring.timezone'))->toDateString();
        $from = $data['from'] ?? Carbon::parse($to)->subDays(29)->toDateString();

        return [$from, $to];
    }

    private function tooLong(string $from, string $to): bool
    {
        return Carbon::parse($from)->diffInDays(Carbon::parse($to)) > 366;
    }

    private function tooLongResponse()
    {
        $msg = 'Pick a period of one year or less.';

        return response()->json(['message' => $msg, 'errors' => ['from' => [$msg]]], 422);
    }
}
