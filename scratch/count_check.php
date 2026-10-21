<?php
use App\Models\ClassAssignment;
use App\Http\Resources\ClassAssignmentResource;
use App\Services\AttendanceService;
$att = app(AttendanceService::class);
foreach (ClassAssignment::with(['section.gradeLevel', 'subject', 'academicYear'])->get() as $ca) {
    $count = (new ClassAssignmentResource($ca))->toArray(request())['student_count'];
    $roster = $att->roster($ca)->count();
    echo "class {$ca->id}: card $count / roster $roster ", $count === $roster ? 'OK' : 'MISMATCH', "\n";
}
