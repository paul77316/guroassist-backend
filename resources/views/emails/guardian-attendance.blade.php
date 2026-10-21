<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; color: #222; line-height: 1.5;">
    <p>Good day,</p>
    <p>This is an attendance notice for <strong>{{ $studentName }}</strong>.</p>
    <p>
        <strong>{{ $statusLabel }}</strong> on {{ $date }}<br>
        Class: {{ $classLabel }}<br>
        Teacher: {{ $teacherName }}
    </p>
    <p>
        If you already told the school about this, you can ignore this message.
        If you have questions, please contact the teacher or the school office.
    </p>
    <p style="font-size: 12px; color: #666;">
        Sent automatically by {{ $appName }}. If you are not this student's parent or guardian,
        please disregard this email and let the school know.
    </p>
</body>
</html>
