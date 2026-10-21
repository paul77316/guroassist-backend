<?php
use App\Jobs\SendGuardianAttendanceNotice;
use App\Mail\GuardianAttendanceNotice;
use App\Models\{Attendance, AttendanceNotification, ClassAssignment, MonitoringSetting, User};
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;

config(['queue.default' => 'sync']);
Mail::fake(); // nothing is really sent

$att = app(AttendanceService::class);
$ca = ClassAssignment::find(1);
$user = User::where('email', 'teacher@demo.local')->first();
$roster = $att->roster($ca)->values()->all();
if (count($roster) < 3) { echo "Needs 3 students in class 1.\n"; return; }
[$a, $b, $c] = $roster;
$ids = [$a->id, $b->id, $c->id];

$tz = config('monitoring.timezone');
$today = Carbon::now($tz)->toDateString();
$yday = Carbon::now($tz)->subDay()->toDateString();
$old = Carbon::now($tz)->subDays(10)->toDateString();
$dates = [$today, $yday, $old];

// snapshot what the test will touch, so it can be restored
$backup = Attendance::where('class_assignment_id', $ca->id)->whereIn('student_id', $ids)->whereIn('attendance_date', $dates)
    ->get()->map(fn ($r) => $r->getAttributes())->all();
$origEmails = collect([$a, $b, $c])->mapWithKeys(fn ($s) => [$s->id => $s->guardian_email]);
$origSetting = MonitoringSetting::where('teacher_id', $ca->teacher_id)->first()?->getAttributes();

$wipe = function () use ($ca, $ids, $dates) {
    AttendanceNotification::whereIn('student_id', $ids)->whereIn('attendance_date', $dates)->delete();
    Attendance::where('class_assignment_id', $ca->id)->whereIn('student_id', $ids)->whereIn('attendance_date', $dates)->delete();
};
$wipe();

$a->update(['guardian_email' => 'guardian.a@example.test']);
$b->update(['guardian_email' => 'guardian.b@example.test']);
$c->update(['guardian_email' => null]);

$set = fn (bool $on, bool $late) => MonitoringSetting::updateOrCreate(
    ['teacher_id' => $ca->teacher_id],
    ['guardian_email_enabled' => $on, 'notify_on_absent' => true, 'notify_on_late' => $late]
);
$sent = fn () => Mail::sent(GuardianAttendanceNotice::class)->count();
$notices = fn () => AttendanceNotification::whereIn('student_id', $ids)->whereIn('attendance_date', $dates)->count();
$mark = fn (string $date, array $marks) => $att->saveBulk($ca, $date,
    collect($marks)->map(fn ($st, $sid) => ['student_id' => $sid, 'status' => $st])->values()->all(), $user->id);

$set(false, false);
$mark($today, [$a->id => 'absent']);
echo "1. Switch off (expect 0 sent, 0 notices): ", $sent(), ", ", $notices(), "\n";

$set(true, false);
$mark($today, [$a->id => 'absent', $b->id => 'late', $c->id => 'absent']);
echo "2. Absent emailed; late (off) and no-email not (expect 1 sent, 1 notice): ", $sent(), ", ", $notices(), "\n";

$mark($today, [$a->id => 'absent', $b->id => 'late', $c->id => 'absent']);
echo "3. Saving again sends nothing new (expect 1): ", $sent(), "\n";

$set(true, true);
$mark($today, [$a->id => 'absent', $b->id => 'late', $c->id => 'absent']);
echo "4. Late switched on (expect 2): ", $sent(), "\n";

$mark($today, [$a->id => 'present']);
$mark($today, [$a->id => 'absent']);
echo "5. Mark changed and changed back, no duplicate (expect 2): ", $sent(), "\n";

$mark($old, [$a->id => 'absent']);
echo "6. 10-day-old date (expect 0 notices, still 2 sent): ",
    AttendanceNotification::where('attendance_date', $old)->where('student_id', $a->id)->count(), ", ", $sent(), "\n";

$nc = AttendanceNotification::create(['student_id' => $c->id, 'class_assignment_id' => $ca->id, 'attendance_date' => $today, 'status' => 'absent', 'state' => 'queued']);
$nb = AttendanceNotification::create(['student_id' => $b->id, 'class_assignment_id' => $ca->id, 'attendance_date' => $today, 'status' => 'absent', 'state' => 'queued']);
(new SendGuardianAttendanceNotice($nc->id))->handle();
(new SendGuardianAttendanceNotice($nb->id))->handle();
echo "7. No email / changed mark are skipped (expect skipped, skipped, 2 sent): {$nc->fresh()->state}, {$nb->fresh()->state}, ", $sent(), "\n";

$set(false, true);
$mark($yday, [$a->id => 'absent']);
$off = AttendanceNotification::where('attendance_date', $yday)->count();
$set(true, true);
$mark($yday, [$a->id => 'absent']);
echo "8. Yesterday: off then on (expect 0, 1, 3 sent): $off, ", AttendanceNotification::where('attendance_date', $yday)->count(), ", ", $sent(), "\n";

$html = (new GuardianAttendanceNotice(AttendanceNotification::where('student_id', $a->id)->where('attendance_date', $today)->first()))->render();
echo "9. Email renders with name and status (expect true, true, no remarks): ",
    json_encode(str_contains($html, $a->first_name)), ", ", json_encode(str_contains($html, 'Marked absent')), "\n";

// cleanup and restore
$wipe();
if ($backup) Attendance::insert($backup);
foreach ([$a, $b, $c] as $s) $s->update(['guardian_email' => $origEmails[$s->id]]);
if ($origSetting) {
    MonitoringSetting::where('teacher_id', $ca->teacher_id)->update(Arr::only($origSetting, ['guardian_email_enabled', 'notify_on_absent', 'notify_on_late']));
} else {
    MonitoringSetting::where('teacher_id', $ca->teacher_id)->delete();
}
echo "10. Restored (expect 0 notices left): ", $notices(), "\n";
