<?php

namespace App\Jobs;

use App\Mail\GuardianAttendanceNotice;
use App\Models\Attendance;
use App\Models\AttendanceNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendGuardianAttendanceNotice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $noticeId) {}

    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(): void
    {
        $notice = AttendanceNotification::with('student')->find($this->noticeId);
        if (! $notice || $notice->state !== 'queued') {
            return;
        }

        $email = $notice->student?->guardian_email;

        // The teacher may have corrected the mark during the delay: only send if it still stands.
        $stillMarked = Attendance::where('student_id', $notice->student_id)
            ->where('class_assignment_id', $notice->class_assignment_id)
            ->whereDate('attendance_date', $notice->attendance_date)
            ->where('status', $notice->status)
            ->exists();

        if (! $email || ! $stillMarked) {
            $notice->update(['state' => 'skipped', 'error' => $email ? 'Mark changed' : 'No guardian email']);

            return;
        }

        Mail::to($email)->send(new GuardianAttendanceNotice($notice));
        $notice->update(['state' => 'sent', 'sent_at' => now(), 'error' => null]);
    }

    /** Only the exception type is stored: messages can contain the email address. */
    public function failed(\Throwable $e): void
    {
        AttendanceNotification::whereKey($this->noticeId)
            ->update(['state' => 'failed', 'error' => substr(class_basename($e), 0, 100)]);
    }
}
