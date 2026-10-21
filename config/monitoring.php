<?php

return [
    // Starting values; each teacher can override them in Settings.
    'attendance_rate_threshold' => 80,
    'consecutive_absence_threshold' => 3,
    'min_days_for_rate' => 5,
    'missing_assignments_threshold' => 3,

    // "Today" for date ranges (the school's timezone, independent of the app timezone).
    'timezone' => 'Asia/Manila',
    'guardian_email_delay_minutes' => 5,
    'guardian_email_max_age_days' => 2,
];