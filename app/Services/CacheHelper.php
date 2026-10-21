<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheHelper
{
    /**
     * Clear all application cache
     */
    public static function clearAll(): void
    {
        Cache::flush();
    }

    /**
     * Clear lookup data cache (grade levels, subjects, academic years, terms)
     */
    public static function clearLookups(): void
    {
        Cache::forget('grade_levels');
        Cache::forget('subjects');
        Cache::forget('academic_years');
        Cache::forget('terms_all');
    }

    /**
     * Clear dashboard cache for a specific user
     */
    public static function clearDashboard(int $userId, string $date): void
    {
        Cache::forget("dashboard_summary_{$userId}_{$date}");
    }

    /**
     * Clear student listing cache
     */
    public static function clearStudents(): void
    {
        // Since student cache keys are dynamic based on query parameters,
        // we clear all cache for simplicity
        Cache::flush();
    }

    /**
     * Clear terms cache for a specific academic year
     */
    public static function clearTerms(int $academicYearId): void
    {
        Cache::forget("terms_{$academicYearId}");
        Cache::forget('terms_all');
    }
}
