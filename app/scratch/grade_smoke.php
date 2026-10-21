<?php
use App\Models\{Assessment, AuditLog, ClassAssignment, Term, User};
use App\Services\{AssessmentService, AttendanceService, GradeCalculationService};

$ca = ClassAssignment::find(1);
$term = Term::find(1);
$user = User::where('email', 'teacher@demo.local')->first();
$as = app(AssessmentService::class);
$gs = app(GradeCalculationService::class);
$student = app(AttendanceService::class)->roster($ca)->first();
$comps = $gs->resolveConfiguration($ca, $term)->components;

try {
    $quiz = $as->create($ca, ['term_id' => 1, 'grading_component_id' => $comps[0]->id, 'title' => 'Quiz 1', 'total_items' => 20], $user);
    $act  = $as->create($ca, ['term_id' => 1, 'grading_component_id' => $comps[1]->id, 'title' => 'Activity 1', 'total_items' => 50], $user);

    echo "Over-max score: ";
    try { $as->saveScores($ca, $term, [['assessment_id' => $quiz->id, 'student_id' => $student->id, 'raw_score' => 25]], $user); echo "NOT REJECTED (bug)\n"; }
    catch (\Illuminate\Validation\ValidationException $e) { echo json_encode($e->errors()), "\n"; }

    $as->saveScores($ca, $term, [
        ['assessment_id' => $quiz->id, 'student_id' => $student->id, 'raw_score' => 18],
        ['assessment_id' => $act->id,  'student_id' => $student->id, 'raw_score' => 40],
    ], $user);
    echo "Grade (expect 85, provisional): ", json_encode($gs->gradebook($ca, $term)['students'][0]['grade']), "\n";

    $as->saveScores($ca, $term, [['assessment_id' => $act->id, 'student_id' => $student->id, 'raw_score' => null]], $user);
    $row = $gs->gradebook($ca, $term)['students'][0];
    echo "After clearing Activity 1 (expect grade 90, missing_count 1): ", $row['grade'], " / ", $row['missing_count'], "\n";

    $as->update($quiz, ['total_items' => 10], $user) ;
} catch (\Throwable $e) {
    echo "Lower total below existing score (expect rejection): ", get_class($e), "\n";
}

echo "Audit rows (expect 8: 2 created assessments, 2 created scores, 1 updated score... see note): ", AuditLog::count(), "\n";

// cleanup
Assessment::withTrashed()->forceDelete();
AuditLog::query()->delete();
echo "Clean: ", Assessment::withTrashed()->count(), "\n";