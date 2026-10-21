<?php
use App\Models\{Attendance, ClassAssignment, MonitoringSetting, User};
use App\Services\{AttendanceService, AttendanceSummaryService};

$svc = app(AttendanceSummaryService::class);
$ca = ClassAssignment::find(1);
$admin = User::where('email', 'admin@demo.local')->first();
$roster = app(AttendanceService::class)->roster($ca)->values()->all();
if (count($roster) < 3) { echo "Needs 3 students in class 1.\n"; return; }
[$a, $b, $c] = $roster;

$dates = ['2030-01-01', '2030-01-02', '2030-01-03', '2030-01-04', '2030-01-05', '2030-01-06'];
$plan = [
    $a->id => ['present', 'present', 'late', 'absent', 'absent', 'absent'],
    $b->id => ['present', 'present', 'present', 'present', 'present', 'excused'],
];
$rows = [];
foreach ($plan as $sid => $statuses) {
    foreach ($statuses as $i => $st) {
        $rows[] = ['student_id' => $sid, 'class_assignment_id' => $ca->id, 'attendance_date' => $dates[$i],
            'status' => $st, 'recorded_by' => $admin->id, 'created_at' => now(), 'updated_at' => now()];
    }
}
Attendance::where('class_assignment_id', $ca->id)->whereBetween('attendance_date', ['2030-01-01', '2030-01-06'])->delete();
Attendance::insert($rows);

$find = fn ($res, $id) => collect($res['students'])->firstWhere('student_id', $id);
$res = $svc->summary($ca, '2030-01-01', '2030-01-06');
$A = $find($res, $a->id); $B = $find($res, $b->id); $C = $find($res, $c->id);

echo "1. Student A (expect P2 L1 A3, 6 days, 50, 3 consecutive): {$A['present']} {$A['late']} {$A['absent']}, {$A['days_counted']} days, {$A['attendance_rate']}, {$A['consecutive_absences']} consecutive\n";
echo "2. A flagged with both reasons (expect true, 2): ", json_encode($A['needs_review']), ", ", count($A['reasons']), " | label: {$A['label']}\n";
echo "3. Student B, excused left out (expect 5 days, 100, excused 1, not flagged): {$B['days_counted']}, {$B['attendance_rate']}, {$B['excused']}, ", json_encode($B['needs_review']), "\n";
echo "4. Student C, no records (expect null, 0 days, not flagged): ", json_encode($C['attendance_rate']), ", {$C['days_counted']}, ", json_encode($C['needs_review']), "\n";
echo "5. Class days and class rate (expect 6, 70): {$res['class_days']}, {$res['summary']['class_rate']}\n";

$early = $svc->summary($ca, '2030-01-01', '2030-01-03');
$A2 = $find($early, $a->id);
echo "6. Shorter period for A (expect 100, 0 consecutive, not flagged): {$A2['attendance_rate']}, {$A2['consecutive_absences']}, ", json_encode($A2['needs_review']), "\n";

$tid = $ca->teacher_id;
if (MonitoringSetting::where('teacher_id', $tid)->exists()) {
    echo "7. Skipped: this teacher already has saved settings.\n";
} else {
    MonitoringSetting::create(['teacher_id' => $tid, 'attendance_rate_threshold' => 80,
        'consecutive_absence_threshold' => 5, 'min_days_for_rate' => 5, 'missing_assignments_threshold' => 3]);
    $A3 = $find($svc->summary($ca, '2030-01-01', '2030-01-06'), $a->id);
    echo "7. Threshold 5 consecutive (expect 1 reason, still flagged): ", count($A3['reasons']), ", ", json_encode($A3['needs_review']), "\n";

    MonitoringSetting::where('teacher_id', $tid)->update(['min_days_for_rate' => 10]);
    $A4 = $find($svc->summary($ca, '2030-01-01', '2030-01-06'), $a->id);
    echo "8. Needs 10 days for a rate (expect 1 reason: consecutive only, 3 < 5 so 0): ", count($A4['reasons']), " ", json_encode($A4['needs_review']), "\n";
    MonitoringSetting::where('teacher_id', $tid)->delete();
}

Attendance::where('class_assignment_id', $ca->id)->whereBetween('attendance_date', ['2030-01-01', '2030-01-06'])->delete();
echo "9. Clean (expect 0): ", Attendance::whereBetween('attendance_date', ['2030-01-01', '2030-01-06'])->count(), "\n";
