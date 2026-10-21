<?php

namespace App\Support;

/**
 * The single place that decides a student's status for an assignment.
 * Pure functions on plain "Y-m-d" strings, so the rules are easy to test.
 */
class AssignmentStatus
{
    // stored on the submission record
    public const SUBMITTED = 'submitted';
    public const LATE = 'late';
    public const EXCUSED = 'excused';

    // derived when there is no record
    public const NOT_YET = 'not_yet';
    public const MISSING = 'missing';
    public const NOT_APPLICABLE = 'not_applicable';

    /**
     * @param string|null $recordStatus stored status, or null when the student has no record
     * @param string      $dueDate      the assignment's due date
     * @param string      $today        today's date in the school timezone
     * @param string|null $enrolledOn   the date the student was added to the class (school timezone)
     * @param string|null $recordedOn   the date the assignment was entered in the system (school timezone)
     */
    public static function resolve(?string $recordStatus, string $dueDate, string $today, ?string $enrolledOn, ?string $recordedOn = null): string
    {
        if ($recordStatus !== null) {
            return $recordStatus;
        }

        // Not applicable only when the student was added to the class after the due date AND after the
        // assignment was entered. An assignment entered later with a back-dated due date therefore still
        // counts students who were already in the class: the date a student was added to the app is not
        // the date they joined the class.
        $cutoff = ($recordedOn !== null && $recordedOn > $dueDate) ? $recordedOn : $dueDate;
        if ($enrolledOn !== null && $enrolledOn > $cutoff) {
            return self::NOT_APPLICABLE;
        }

        // The due date itself is still "not yet"; it becomes "missing" the next day.
        return $dueDate < $today ? self::MISSING : self::NOT_YET;
    }

    /** Stored status for a hand-in date: on or before the due date is on time. */
    public static function forDate(string $submittedDate, string $dueDate): string
    {
        return $submittedDate > $dueDate ? self::LATE : self::SUBMITTED;
    }
}
