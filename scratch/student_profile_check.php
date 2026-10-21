<?php
use App\Http\Controllers\Api\StudentProfileController;
use App\Models\{ClassAssignment, User};
use App\Services\{AttendanceService, AttendanceSummaryService};
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

$ctl = app(StudentProfileController::class);
$svc = app(AttendanceSummaryService::class);
$student = app(AttendanceService::class)->roster(ClassAssignment::find(1))->first();
if (! $student) { echo "Needs a student in class 1.\n"; return; }

$call = function (string $email) use ($ctl, $svc, $student) {
    $u = User::where('email', $email)->first();
    Auth::setUser($u);
    $req = Request::create('/', 'GET');
    $req->setUserResolver(fn () => $u);
    try {
        return $ctl->show($req, $student, $svc)->getData(true)['data'];
    } catch (AuthorizationException $e) {
        return null;
    }
};

$t1 = $call('teacher@demo.local');
$t2 = $call('teacher2@demo.local');
$ad = $call('admin@demo.local');

echo "1. Teacher 1 sees the profile (expect true): ", json_encode($t1 !== null), "\n";
echo "2. Has guardian_email, age and grade level fields (expect true, true, true): ",
    json_encode(array_key_exists('guardian_email', $t1)), ", ", json_encode(isset($t1['age'])), ", ", json_encode(array_key_exists('grade_level', $t1)), "\n";
echo "3. Classes listed with attendance (expect >= 1, true): ", count($t1['classes']), ", ",
    json_encode(isset($t1['classes'][0]['attendance']['attendance_rate']) || array_key_exists('attendance_rate', $t1['classes'][0]['attendance'])), "\n";

$own = ClassAssignment::where('teacher_id', User::where('email', 'teacher@demo.local')->first()->teacher->id)->pluck('id')->all();
echo "4. Teacher 1 sees only their own classes (expect true): ",
    json_encode(collect($t1['classes'])->every(fn ($c) => in_array($c['id'], $own))), "\n";
echo "5. Teacher 1 does not get teacher names (expect true): ",
    json_encode(collect($t1['classes'])->every(fn ($c) => $c['teacher'] === null)), "\n";
echo "6. Teacher 2 is blocked (expect true): ", json_encode($t2 === null), "\n";
echo "7. Admin sees it with teacher names (expect true, true): ",
    json_encode($ad !== null), ", ", json_encode(count($ad['classes']) >= count($t1['classes']) && $ad['classes'][0]['teacher'] !== null), "\n";
