<?php

namespace App\Support;

/** Controlled lists (value => neutral label) used by validation and by the screens. */
class InterventionVocabulary
{
    public const CONCERN_TYPES = [
        'attendance_concern' => 'Attendance concern',
        'missing_work' => 'Missing work',
        'other' => 'Other',
    ];

    public const INTERVENTION_TYPES = [
        'consultation' => 'Individual consultation',
        'tutoring' => 'Tutoring',
        'parent_meeting' => 'Parent meeting',
        'other' => 'Other',
    ];

    public const OUTCOMES = [
        'improved' => 'Improved',
        'partly_improved' => 'Partly improved',
        'no_change' => 'No change',
        'not_yet_known' => 'Not yet known',
    ];

    public const METHODS = [
        'phone_call' => 'Phone call',
        'text' => 'Text message',
        'in_person' => 'In person',
        'email' => 'Email',
        'other' => 'Other',
    ];

    /** @param 'CONCERN_TYPES'|'INTERVENTION_TYPES'|'OUTCOMES'|'METHODS' $set */
    public static function keys(string $set): array
    {
        return array_keys(constant(self::class . '::' . $set));
    }

    public static function label(string $set, ?string $key): ?string
    {
        if ($key === null) {
            return null;
        }

        return constant(self::class . '::' . $set)[$key] ?? $key;
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function pairs(string $set): array
    {
        $out = [];
        foreach (constant(self::class . '::' . $set) as $value => $label) {
            $out[] = ['value' => $value, 'label' => $label];
        }

        return $out;
    }
}
