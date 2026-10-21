<?php
use App\Models\{AcademicYear, AuditLog, GradeLevel, Subject, User};
use App\Services\SetupService;
use Illuminate\Validation\ValidationException;

$svc = app(SetupService::class);
$admin = User::where('email', 'admin@demo.local')->first();
$expect = function (callable $f) {
    try { $f(); return 'NOT REJECTED (bug)'; } catch (ValidationException $e) { return json_encode($e->errors()); }
};

$gl = $svc->create(GradeLevel::class, ['name' => 'ZZ Grade', 'order' => 7], $admin);
$sub = $svc->create(Subject::class, ['name' => 'ZZ Science', 'code' => 'ZZSCI'], $admin);
$year = $svc->create(AcademicYear::class, ['name' => 'ZZ 2099', 'start_date' => '2099-06-01', 'end_date' => '2100-03-31', 'is_active' => true], $admin);
echo "1. Created: {$gl->name}, {$sub->code}, {$year->name}\n";
echo "2. New year is active, year 1 switched off (expect true, false): ",
    json_encode((bool) $year->fresh()->is_active), ", ", json_encode((bool) AcademicYear::find(1)->is_active), "\n";

$svc->update(AcademicYear::find(1), ['is_active' => true], $admin);
echo "3. Year 1 active again, only one active (expect true, 1): ",
    json_encode((bool) AcademicYear::find(1)->is_active), ", ", AcademicYear::where('is_active', true)->count(), "\n";

echo "4. Delete used grade level (expect rejection): ", $expect(fn () => $svc->delete(GradeLevel::find(1), $admin)), "\n";
echo "5. Delete used subject (expect rejection): ", $expect(fn () => $svc->delete(Subject::find(1), $admin)), "\n";
echo "6. Delete used academic year (expect rejection): ", $expect(fn () => $svc->delete(AcademicYear::find(1), $admin)), "\n";

$svc->update($gl, ['name' => 'ZZ Grade Renamed', 'order' => 7], $admin);
echo "7. Rename works (expect ZZ Grade Renamed): {$gl->fresh()->name}\n";

$svc->delete($gl, $admin); $svc->delete($sub, $admin); $svc->delete($year, $admin);
echo "8. Unused items deleted (expect 0 left): ",
    GradeLevel::where('name', 'like', 'ZZ%')->count() + Subject::where('name', 'like', 'ZZ%')->count() + AcademicYear::where('name', 'like', 'ZZ%')->count(), "\n";

echo "9. Audit rows (expect > 0): ", AuditLog::count(), "\n";
AuditLog::whereIn('auditable_type', [GradeLevel::class, Subject::class, AcademicYear::class])->delete();
echo "10. Original data intact (expect 1, 2, 1): ", GradeLevel::count(), ", ", Subject::count(), ", ", AcademicYear::count(), "\n";
