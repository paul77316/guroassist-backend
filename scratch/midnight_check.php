<?php
use App\Http\Requests\BulkStoreAttendanceRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

$tz = config('monitoring.timezone');
Carbon::setTestNow(Carbon::parse('2026-10-10 00:30:00', $tz));   // still Oct 9 in UTC

$data = ['class_assignment_id' => 1, 'date' => '2026-10-10', 'records' => [['student_id' => 2, 'status' => 'present']]];
$rules = (new BulkStoreAttendanceRequest())->rules();

echo "Server clock (UTC) says: ", now()->utc()->toDateTimeString(), "\n";
echo "1. Today's date at 12:30 AM Manila (expect no date error): ",
    json_encode(Validator::make($data, $rules)->errors()->get('date')), "\n";

$data['date'] = '2026-10-11';
echo "2. Tomorrow (expect an error): ",
    json_encode(Validator::make($data, $rules)->errors()->get('date')), "\n";
