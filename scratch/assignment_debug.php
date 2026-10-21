<?php
use App\Models\{Assignment, Enrollment};
use App\Services\AssignmentService;

$a = Assignment::latest('id')->first();
if (! $a) { echo "No assignments yet.\n"; return; }

$g = app(AssignmentService::class)->grid($a);
echo "Assignment #{$a->id} due {$a->due_date->format('Y-m-d')}, entered {$a->created_at}\n";
foreach ($g['students'] as $s) {
    $e = Enrollment::where('class_assignment_id', $a->class_assignment_id)->where('student_id', $s['student_id'])->first();
    echo "student {$s['student_id']} | {$s['status']} | added to the class {$e?->created_at}\n";
}
echo json_encode($g['stats']), "\n";
