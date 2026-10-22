<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TeacherWelcomeMail extends Mailable
{
    public function __construct(
        public string $teacherName,
        public string $email,
        public string $initialPassword,
        public string $expiresAt,
        public bool $isPasswordReset = false,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->isPasswordReset ? 'Your GuroAssist password was reset' : 'Welcome to GuroAssist',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.teacher-welcome',
            text: 'emails.teacher-welcome-text',
            with: [
                'teacherName' => $this->teacherName,
                'email' => $this->email,
                'initialPassword' => $this->initialPassword,
                'expiresAt' => $this->expiresAt,
                'loginUrl' => rtrim(config('app.url'), '/').'/login',
                'appName' => config('app.name'),
                'isPasswordReset' => $this->isPasswordReset,
            ],
        );
    }
}
