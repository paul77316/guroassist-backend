<?php

namespace App\Mail;

use App\Models\AttendanceNotification;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class GuardianAttendanceNotice extends Mailable
{
    public function __construct(public AttendanceNotification $notice)
    {
        $this->notice->loadMissing(
            'student', 'classAssignment.subject', 'classAssignment.section.gradeLevel', 'classAssignment.teacher.user'
        );
    }

    public function envelope(): Envelope
    {
        // First name only: subjects show on lock screens.
        return new Envelope(subject: 'Attendance notice: ' . $this->notice->student->first_name);
    }

    public function content(): Content
    {
        $n = $this->notice;
        $ca = $n->classAssignment;

        return new Content(
            view: 'emails.guardian-attendance',
            text: 'emails.guardian-attendance-text',
            with: [
                'studentName' => $n->student->full_name,
                'statusLabel' => $n->status === 'late' ? 'Marked late' : 'Marked absent',
                'date' => $n->attendance_date->format('l, F j, Y'),
                'classLabel' => "{$ca->section->gradeLevel->name} - {$ca->section->name} ({$ca->subject->name})",
                'teacherName' => $ca->teacher->user->name,
                'appName' => config('app.name'),
            ]
        );
    }
}
