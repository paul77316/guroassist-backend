Hello {{ $teacherName }},

@if ($isPasswordReset)
An administrator reset the password for your {{ $appName }} account.
@else
Welcome to {{ $appName }}. Your teacher account is ready.
@endif

Sign in at {{ $loginUrl }} using:
Email: {{ $email }}
Initial password: {{ $initialPassword }}

You must change this password after signing in. It expires at {{ $expiresAt }} if you do not update it.
If the password has expired, contact your school administrator to reset it.

Sent automatically by {{ $appName }}.
