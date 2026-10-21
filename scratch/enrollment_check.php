<?php
use App\Models\{AuditLog, ClassAssignment, Enrollment, User};
use App\Services\{AttendanceService, EnrollmentService};
use Illuminate\Validation\ValidationException;

$att = app(AttendanceService::class);
$svc = app(EnrollmentService::class);
$admin = User::where('email', 'admin@demo.local')->first();
$t1 = User::where('email', 'teacher@demo.local')->first();
$t2 = User::where('email', 'teacher2@demo.local')->first();
$ca1 = ClassAssignment::find(1);
$ca2 = ClassAssignment::find(2);
$expect = function (callable $f) {
    try { $f(); return 'NOT REJECTED (bug)'; } catch (ValidationException $e) { return json_encode($e->errors()); }
};

echo "1. Rosters unchanged by the migration:\n";
$before = json_decode(file_get_contents(base_path('scratch/roster_before.json')), true);
$sameRosters = function () use ($before, $att) {
    $ok = true;
    foreach ($before as $classId => $ids) {
        $now = $att->roster(ClassAssignment::find($classId))->pluck('id')->sort()->values()->all();
        $ok = $ok && $now == $ids;
        echo "   class $classId: ", $now == $ids ? 'OK' : 'MISMATCH before=' . json_encode($ids) . ' after=' . json_encode($now), "\n";
    }
    return $ok;
};
$sameRosters();
echo "2. Unlinked enrollments (expect 0): ", Enrollment::whereNull('class_assignment_id')->count(), "\n";

$stu = $att->roster($ca2)->first();
echo "3. Enroll class-2 student in class 1 (expect 1): ", $svc->enroll($ca1, [$stu->id], $admin), "\n";
echo "4. Enroll again (expect 0): ", $svc->enroll($ca1, [$stu->id], $admin), "\n";
echo "5. In class 1 and still in class 2 (expect true, true): ",
    json_encode($att->roster($ca1)->contains('id', $stu->id)), ", ", json_encode($att->roster($ca2)->contains('id', $stu->id)), "\n";
$svc->remove($ca1, $stu, $admin);
echo "6. After remove: class 1 (expect false), class 2 (expect true): ",
    json_encode($att->roster($ca1)->contains('id', $stu->id)), ", ", json_encode($att->roster($ca2)->contains('id', $stu->id)), "\n";

echo "7. Teacher 1 can add that student (expect true): ", json_encode($svc->candidateQuery($ca1, $t1)->where('id', $stu->id)->exists()), "\n";
echo "8. Teacher 2 has no candidates (expect 0): ", $svc->candidateQuery($ca1, $t2)->count(), "\n";
echo "9. Teacher 2 adds student (expect rejection): ", $expect(fn () => $svc->enrollVisible($ca1, [$stu->id], $t2)), "\n";
echo "10. Remove a student who isn't enrolled (expect rejection): ", $expect(fn () => $svc->remove($ca1, $stu, $admin)), "\n";

echo "11. Copy class 2 roster into class 1 (expect >= 1): ", $n = $svc->copyFrom($ca1, $ca2, $admin), "\n";
foreach ($att->roster($ca2) as $s) { if ($att->roster($ca1)->contains('id', $s->id) && ! in_array($s->id, $before[1])) $svc->remove($ca1, $s, $admin); }
echo "12. Audit rows (expect > 0): ", AuditLog::count(), "\n";

AuditLog::query()->delete();
echo "13. Rosters restored: "; echo $sameRosters() ? "yes\n" : "NO\n";
