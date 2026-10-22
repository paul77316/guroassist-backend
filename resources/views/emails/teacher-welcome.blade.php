<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; color: #222; line-height: 1.5;">
    <p>Hello {{ $teacherName }},</p>
    @if ($isPasswordReset)
        <p>An administrator reset the password for your {{ $appName }} account.</p>
    @else
        <p>Welcome to {{ $appName }}. Your teacher account is ready.</p>
    @endif
    <p>
        Sign in at <a href="{{ $loginUrl }}">{{ $loginUrl }}</a> using:<br>
        Email: <strong>{{ $email }}</strong><br>
        Initial password: <strong>{{ $initialPassword }}</strong>
    </p>
    <p>You must change this password after signing in. It expires at {{ $expiresAt }} if you do not update it.</p>
    <p>If the password has expired, contact your school administrator to reset it.</p>
    <p style="font-size: 12px; color: #666;">Sent automatically by {{ $appName }}.</p>
</body>
</html>
