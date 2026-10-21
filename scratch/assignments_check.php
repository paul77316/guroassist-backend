<?php
use App\Models\{Assignment, AuditLog, ClassAssignment};
use App\Services\{AssignmentService, AttendanceService};
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

$svc = app(AssignmentService::class);
$att = app(AttendanceService::class);

$ca = ClassAssignment::where('status', 'active')->get()->first(fn ($c) => $att->roster($c)->count() >= 3);
if (! $ca) { echo "Needs an active class with at least 3 students.\n"; return; }
$user = $ca->teacher->user;
$roster = $att->roster($ca)->values();
$n = $roster->count();
[$a, $b, $c] = [$roster[0], $roster[1], $roster[2]];
echo "Using class {$ca->id} with $n students\n";

$tz = config('monitoring.timezone');
$d = fn (int $daysAgo) => Carbon::now($tz)->subDays($daysAgo)->toDateString();
$expect = function (callable $f) {
    try { $f(); return 'NOT REJECTED (bug)'; } catch (ValidationException $e) { return json_encode($e->errors()); }
};
$missingOf = fn ($studentId) => collect($svc->missing($ca)['students'])->firstWhere('student_id', $studentId)['missing_count'];
$row = fn ($grid, $id) => collect($grid['students'])->firstWhere('student_id', $id);

// existing past-due work in this class also counts, so measure from a baseline
$baseA = $missingOf($a->id);
$baseC = $missingOf($c->id);

$mk = fn (string $title, int $assignedAgo, string $due) => $svc->create($ca, [
    'title' => $title, 'description' => null, 'assigned_date' => $d($assignedAgo), 'due_date' => $due,
], $user);
$A1 = $mk('ZZ Test 1', 7, $d(1));
$A2 = $mk('ZZ Test 2', 7, $d(2));
$A3 = $mk('ZZ Test 3', 7, $d(3));
$B = $mk('ZZ Test Future', 0, Carbon::now($tz)->addDays(5)->toDateString());

$g = $svc->grid($A1);
echo "1. Past due, nothing recorded: all missing (expect true, $n): ",
    json_encode(collect($g['students'])->every(fn ($s) => $s['status'] === 'missing')), ", {$g['stats']['missing']}\n";
$gb = $svc->grid($B);
echo "2. Not due yet: all 'not_yet' (expect true, $n): ",
    json_encode(collect($gb['students'])->every(fn ($s) => $s['status'] === 'not_yet')), ", {$gb['stats']['not_yet']}\n";

$svc->saveSubmissions($A1, [
    ['student_id' => $a->id, 'status' => 'submitted', 'submitted_date' => $d(2), 'feedback' => 'On time'],
    ['student_id' => $b->id, 'status' => 'submitted', 'submitted_date' => $d(0)],
    ['student_id' => $c->id, 'status' => 'excused'],
], $user);
$g = $svc->grid($A1);
echo "3. On time, handed in after the due date (auto late), excused (expect submitted, late, excused): ",
    $row($g, $a->id)['status'], ", ", $row($g, $b->id)['status'], ", ", $row($g, $c->id)['status'], "\n";
$expectedRate = round(2 / ($n - 1) * 100, 1);
echo "4. Counts and rate, excused left out (expect 1, 1, 1, " . ($n - 3) . ", $expectedRate): ",
    "{$g['stats']['submitted']}, {$g['stats']['late']}, {$g['stats']['excused']}, {$g['stats']['missing']}, {$g['stats']['submission_rate']}\n";
echo "5. Feedback is kept (expect On time): ", $row($g, $a->id)['feedback'], "\n";

$svc->saveSubmissions($A1, [['student_id' => $a->id, 'status' => null]], $user);
$g = $svc->grid($A1);
echo "6. Cleared mark goes back to missing (expect missing, false): ", $row($g, $a->id)['status'], ", ", json_encode($row($g, $a->id)['recorded']), "\n";

echo "7a. Future date (expect rejection): ", $expect(fn () => $svc->saveSubmissions($A1, [['student_id' => $a->id, 'status' => 'submitted', 'submitted_date' => Carbon::now($tz)->addDay()->toDateString()]], $user)), "\n";
echo "7b. Date before assigned (expect rejection): ", $expect(fn () => $svc->saveSubmissions($A1, [['student_id' => $a->id, 'status' => 'submitted', 'submitted_date' => $d(8)]], $user)), "\n";
echo "7c. Student not in the class (expect rejection): ", $expect(fn () => $svc->saveSubmissions($A1, [['student_id' => 999999, 'status' => 'submitted']], $user)), "\n";
echo "7d. Date without status (expect rejection): ", $expect(fn () => $svc->saveSubmissions($A1, [['student_id' => $a->id, 'status' => null, 'submitted_date' => $d(1)]], $user)), "\n";

$m = $svc->missing($ca);
echo "8. Missing work for A is +3 and for C (excused on one) +2 (expect true, true): ",
    json_encode($missingOf($a->id) === $baseA + 3), ", ", json_encode($missingOf($c->id) === $baseC + 2), "\n";
$ra = collect($m['students'])->firstWhere('student_id', $a->id);
echo "9. Review flag follows the threshold (expect true): ",
    json_encode($ra['needs_review'] === ($ra['missing_count'] >= $m['threshold'])), " (threshold {$m['threshold']})\n";

$svc->saveSubmissions($A2, [['student_id' => $a->id, 'status' => 'late']], $user);
echo "10. Handing in late work removes it from missing (expect true): ", json_encode($missingOf($a->id) === $baseA + 2), "\n";

$svc->delete($A3, $user);
echo "11. Deleted assignment no longer counts (expect true): ", json_encode($missingOf($a->id) === $baseA + 1), "\n";

$list = collect($svc->listForClass($ca));
$futureStats = $list->first(fn ($i) => $i['assignment']->id === $B->id)['stats'];
echo "12. List shows counts (expect $n not yet, 0 missing): {$futureStats['not_yet']}, {$futureStats['missing']}\n";

$ids = Assignment::withTrashed()->where('title', 'like', 'ZZ Test%')->pluck('id');
echo "13. Audit rows (expect >= 5): ", AuditLog::where('auditable_type', Assignment::class)->whereIn('auditable_id', $ids)->count(), "\n";

AuditLog::where('auditable_type', Assignment::class)->whereIn('auditable_id', $ids)->delete();
Assignment::withTrashed()->whereIn('id', $ids)->forceDelete();
echo "14. Clean (expect 0, back to baseline true): ", Assignment::withTrashed()->where('title', 'like', 'ZZ Test%')->count(), ", ",
    json_encode($missingOf($a->id) === $baseA), "\n";
