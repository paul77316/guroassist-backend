<?php

namespace App\Support;

/**
 * Intervention statuses and the one table of allowed transitions.
 * The service uses this; nothing else decides transitions.
 */
class InterventionStatus
{
    public const PLANNED = 'planned';
    public const IN_PROGRESS = 'in_progress';
    public const REQUIRES_FOLLOW_UP = 'requires_follow_up';
    public const COMPLETED = 'completed';

    public const ALL = [self::PLANNED, self::IN_PROGRESS, self::REQUIRES_FOLLOW_UP, self::COMPLETED];

    /** from => allowed targets. Completed -> In Progress is the audited reopen. */
    public const TRANSITIONS = [
        self::PLANNED => [self::IN_PROGRESS, self::COMPLETED],
        self::IN_PROGRESS => [self::REQUIRES_FOLLOW_UP, self::COMPLETED],
        self::REQUIRES_FOLLOW_UP => [self::IN_PROGRESS, self::COMPLETED],
        self::COMPLETED => [self::IN_PROGRESS],
    ];

    public static function canMove(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public static function label(string $status): string
    {
        return [
            self::PLANNED => 'Planned',
            self::IN_PROGRESS => 'In Progress',
            self::REQUIRES_FOLLOW_UP => 'Requires Follow-up',
            self::COMPLETED => 'Completed',
        ][$status] ?? $status;
    }
}
