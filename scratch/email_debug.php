<?php
use App\Models\{AttendanceNotification, ClassAssignment, MonitoringSetting};
use App\Services\AttendanceService;
use Illuminate\Support\Facades\Schema;

echo "mail.default: ", config('mail.default'), " | queue.default: ", config('queue.default'), "\n";
echo "smtp host:port: ", config('mail.mailers.smtp.host'), ":", config('mail.mailers.smtp.port'),
    " | username set: ", config('mail.mailers.smtp.username') ? 'yes' : 'NO',
    " | password set: ", config('mail.mailers.smtp.password') ? 'yes' : 'NO', "\n";
echo "from address: ", config('mail.from.address'), "\n";
echo "delay/max age: ", config('monitoring.guardian_email_delay_minutes', 'NOT SET'), " min / ", config('monitoring.guardian_email_max_age_days', 'NOT SET'), " days\n";

echo "students.guardian_email column: ", Schema::hasColumn('students', 'guardian_email') ? 'yes' : 'NO (run the migration)', "\n";
echo "attendance_notifications table: ", Schema::hasTable('attendance_notifications') ? 'yes' : 'NO (run the migration)', "\n";
echo "AttendanceService calls the notifier: ",
    str_contains(file_get_contents(app_path('Services/AttendanceService.php')), 'GuardianNotificationService') ? 'yes' : 'NO (file not replaced)', "\n";

$ca = ClassAssignment::find(1);
$row = MonitoringSetting::where('teacher_id', $ca->teacher_id)->first();
echo "teacher settings: ", $row
    ? json_encode($row->only(['guardian_email_enabled', 'notify_on_absent', 'notify_on_late']))
    : 'NO ROW (the Settings switch was never saved)', "\n";

$roster = app(AttendanceService::class)->roster($ca);
echo "class 1 students with a guardian email: ", $roster->filter(fn ($s) => $s->guardian_email)->count(), " of ", $roster->count(), "\n";

echo "--- last 10 notices\n";
if (Schema::hasTable('attendance_notifications')) {
    AttendanceNotification::latest('id')->take(10)->get()->each(fn ($n) =>
        print("#{$n->id} student {$n->student_id} | {$n->attendance_date->format('Y-m-d')} | {$n->status} | {$n->state}" . ($n->error ? " | {$n->error}" : '') . "\n"));
}
echo "--- queue tables\n";
foreach (['jobs', 'failed_jobs'] as $t) {
    echo "$t: ", Schema::hasTable($t) ? \Illuminate\Support\Facades\DB::table($t)->count() : 'table missing', "\n";
}
