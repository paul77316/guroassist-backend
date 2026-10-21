<?php
use App\Models\{AuditLog, ClassAssignment, Student, User};
use App\Services\{AttendanceService, EnrollmentService, RosterImportService};
use Illuminate\Http\UploadedFile;

$svc = app(RosterImportService::class);
$enr = app(EnrollmentService::class);
$att = app(AttendanceService::class);
$admin = User::where('email', 'admin@demo.local')->first();
$t1 = User::where('email', 'teacher@demo.local')->first();
$t2 = User::where('email', 'teacher2@demo.local')->first();
$ca1 = ClassAssignment::find(1);
$ca2 = ClassAssignment::find(2);

$inClass1 = $att->roster($ca1)->first();
$archived = Student::withTrashed()->where('status', 'archived')->first();
if (! $inClass1 || ! $archived) {
    echo "Needs one student in class 1 and one archived student.\n";
    return;
}
$before = $att->roster($ca1)->count();

// self-contained: a student who is only in class 2
$onlyClass2 = Student::create([
    'student_id' => 'TEST-EXIST', 'first_name' => 'Eve', 'last_name' => 'Exist',
    'sex' => 'female', 'date_of_birth' => '2013-02-02', 'status' => 'active',
]);
$enr->enroll($ca2, [$onlyClass2->id], $admin);

$csv = "student_id,first_name,middle_name,last_name,sex,date_of_birth\n"
 . "TEST-001,Ana,,Cruz,F,5/20/2013\n"
 . "{$inClass1->student_id},,,,,\n"
 . "TEST-EXIST,,,,,\n"
 . "TEST-002,Ben,,,male,2013-01-01\n"
 . "TEST-003,Cy,,Lim,male,31/31/2013\n"
 . "TEST-001,Dup,,Dup,male,2013-01-01\n"
 . "{$archived->student_id},,,,,\n";
$path = base_path('scratch/import_test.csv');
file_put_contents($path, $csv);
$file = fn () => new UploadedFile($path, 'import_test.csv', 'text/csv', null, true);

$show = function ($label, $plan) use ($svc) {
    $s = $svc->summarize($plan);
    echo "$label: ", json_encode($s['counts']), "\n";
    foreach ($s['rows'] as $r) echo "   row {$r['row']} [{$r['action']}] {$r['message']}\n";
};

$show('1. Teacher 1 preview (expect create 1, enroll 1, already 1, error 4)', $svc->plan($ca1, $file(), $t1));
$p2 = $svc->summarize($svc->plan($ca1, $file(), $t2));
echo "2. Teacher 2 (expect create 1, enroll 0, already 1, error 5): ", json_encode($p2['counts']), "\n";
echo "3. Preview changed nothing (expect true): ", json_encode($att->roster($ca1)->count() === $before && ! Student::where('student_id', 'TEST-001')->exists()), "\n";

$r = $svc->commit($ca1, $svc->plan($ca1, $file(), $t1), $t1);
echo "4. Commit (expect created 1, enrolled 2): created {$r['created']}, enrolled {$r['enrolled']}\n";
echo "5. Roster grew by 2 (expect true): ", json_encode($att->roster($ca1)->count() === $before + 2), "\n";
$r = $svc->commit($ca1, $svc->plan($ca1, $file(), $t1), $t1);
echo "6. Second commit adds nothing (expect 0, 0): {$r['created']}, {$r['enrolled']}\n";
echo "7. Audit has IDs only: ", json_encode(AuditLog::where('event', 'created')->where('auditable_type', Student::class)->first()?->new_values), "\n";

// cleanup (enrollments cascade when the students are deleted)
Student::withTrashed()->whereIn('student_id', ['TEST-001', 'TEST-EXIST'])->get()->each->forceDelete();
AuditLog::query()->delete();
unlink($path);
echo "8. Restored (expect true): ", json_encode($att->roster($ca1)->count() === $before && ! Student::withTrashed()->where('student_id', 'like', 'TEST-%')->exists()), "\n";
