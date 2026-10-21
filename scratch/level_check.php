<?php
use App\Models\{AcademicYear, AuditLog, ClassAssignment, GradeLevel, Section, Student, StudentGradeLevel, Subject, User};
use App\Services\{EnrollmentService, RosterImportService};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

$enr = app(EnrollmentService::class);
$imp = app(RosterImportService::class);
$admin = User::where('email', 'admin@demo.local')->first();
$t1 = User::where('email', 'teacher@demo.local')->first();
$expect = function (callable $f) {
    try { $f(); return 'NOT REJECTED (bug)'; } catch (ValidationException $e) { return json_encode($e->errors()); }
};

$pairs = DB::table('enrollments')->select('student_id', 'academic_year_id')->distinct()->get()->count();
echo "1. Backfill (expect equal): ", StudentGradeLevel::count(), " records / $pairs student-years\n";

$year = AcademicYear::where('is_active', true)->first();
$g9 = GradeLevel::create(['name' => 'ZZ Grade 9', 'order' => 9]);
$sec = Section::create(['name' => 'ZZ Rizal', 'grade_level_id' => $g9->id, 'academic_year_id' => $year->id]);
$ca9 = ClassAssignment::create(['teacher_id' => $t1->teacher->id, 'subject_id' => Subject::first()->id,
    'section_id' => $sec->id, 'academic_year_id' => $year->id, 'status' => 'active']);
$ca1 = ClassAssignment::find(1);
$maria = Student::find(2);

echo "2. Grade 8 student into Grade 9 class (expect rejection): ", $expect(fn () => $enr->enroll($ca9, [$maria->id], $admin)), "\n";
echo "3. Not offered as a candidate (expect false): ", json_encode($enr->candidateQuery($ca9, $t1)->where('id', $maria->id)->exists()), "\n";

$new = Student::create(['student_id' => 'TEST-LVL', 'first_name' => 'Lia', 'last_name' => 'Level',
    'sex' => 'female', 'date_of_birth' => '2012-02-02', 'status' => 'active']);
echo "4. New student joins Grade 9 class (expect 1): ", $enr->enroll($ca9, [$new->id], $admin), "\n";
echo "5. Level set from the class (expect true): ",
    json_encode((int) StudentGradeLevel::where('student_id', $new->id)->where('academic_year_id', $year->id)->value('grade_level_id') === $g9->id), "\n";
echo "6. Same student into Grade 8 class (expect rejection): ", $expect(fn () => $enr->enroll($ca1, [$new->id], $admin)), "\n";
echo "7. Copy roster across grade levels (expect rejection): ", $expect(fn () => $enr->copyFrom($ca9, $ca1, $t1)), "\n";

$path = base_path('scratch/level_test.csv');
file_put_contents($path, "student_id\n{$maria->student_id}\n");
$plan = $imp->summarize($imp->plan($ca9, new UploadedFile($path, 'level_test.csv', 'text/csv', null, true), $t1));
echo "8. Import row for a Grade 8 student (expect error): ", json_encode($plan['counts']), " ", $plan['rows'][0]['message'] ?? '', "\n";
unlink($path);

Student::withTrashed()->where('student_id', 'TEST-LVL')->get()->each->forceDelete();
$ca9->forceDelete(); $sec->delete(); $g9->delete();
AuditLog::query()->delete();
echo "9. Restored (expect 0 test rows): ", GradeLevel::where('name', 'like', 'ZZ%')->count() + Student::withTrashed()->where('student_id', 'like', 'TEST-%')->count(), "\n";
