<?php
use App\Models\{Attendance, ClassAssignment, User};
use App\Services\{AttendanceService, AttendanceSummaryService};

$svc = app(AttendanceSummaryService::class);
$ca = ClassAssignment::find(1);
$admin = User::where('email', 'admin@demo.local')->first();
$roster = app(AttendanceService::class)->roster($ca)->values()->all();
if (count($roster) < 3) { echo "Needs 3 students in class 1.\n"; return; }
[$a, $b, $c] = $roster;

$dates = ['2030-01-01', '2030-01-02', '2030-01-03', '2030-01-04', '2030-01-05', '2030-01-06'];
$plan = [
    $a->id => [$dates[0] => 'present', $dates[1] => 'present', $dates[2] => 'late', $dates[3] => 'absent', $dates[4] => 'absent', $dates[5] => 'absent'],
    $b->id => [$dates[0] => 'present', $dates[1] => 'present', $dates[2] => 'present', $dates[3] => 'present', $dates[4] => 'present', $dates[5] => 'excused'],
    $c->id => [$dates[1] => 'present'],
];
$rows = [];
foreach ($plan as $sid => $marks) {
    foreach ($marks as $date => $st) {
        $rows[] = ['student_id' => $sid, 'class_assignment_id' => $ca->id, 'attendance_date' => $date,
            'status' => $st, 'remarks' => $st === 'excused' ? 'Medical' : null,
            'recorded_by' => $admin->id, 'created_at' => now(), 'updated_at' => now()];
    }
}
Attendance::where('class_assignment_id', $ca->id)->whereBetween('attendance_date', ['2030-01-01', '2030-01-06'])->delete();
Attendance::insert($rows);

$on = fn ($h, $status) => $h['records'] ? collect($h['records'])->where('status', $status)->pluck('date')->values()->all() : [];

$A = $svc->studentHistory($ca, $a, '2030-01-01', '2030-01-06');
echo "1. A: records (expect 6): ", count($A['records']), "\n";
echo "2. A absent dates (expect 2030-01-04,05,06): ", implode(',', $on($A, 'absent')), "\n";
echo "3. A late dates (expect 2030-01-03): ", implode(',', $on($A, 'late')), "\n";
echo "4. A stats match the class report (expect 50, 3, true): {$A['stats']['attendance_rate']}, {$A['stats']['consecutive_absences']}, ", json_encode($A['stats']['needs_review']), "\n";

$B = $svc->studentHistory($ca, $b, '2030-01-01', '2030-01-06');
$ex = collect($B['records'])->firstWhere('status', 'excused');
echo "5. B excused date and remark (expect 2030-01-06, Medical): {$ex['date']}, {$ex['remarks']}\n";

$C = $svc->studentHistory($ca, $c, '2030-01-01', '2030-01-06');
echo "6. C records and unmarked class days (expect 1, 5): ", count($C['records']), ", ", count($C['unmarked_dates']), "\n";

$none = $svc->studentHistory($ca, $a, '2030-02-01', '2030-02-28');
echo "7. Empty period (expect 0 records, null rate): ", count($none['records']), ", ", json_encode($none['stats']['attendance_rate']), "\n";

Attendance::where('class_assignment_id', $ca->id)->whereBetween('attendance_date', ['2030-01-01', '2030-01-06'])->delete();
echo "8. Clean (expect 0): ", Attendance::whereBetween('attendance_date', ['2030-01-01', '2030-01-06'])->count(), "\n";
