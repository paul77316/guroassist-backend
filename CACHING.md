# Caching Implementation

## Overview

Caching has been implemented to improve application performance by reducing database queries for frequently accessed data. Currently using database-based caching (CACHE_STORE=database in .env).

## Cache Configuration

The application is configured to use database caching. To switch to Redis (recommended for production):

1. Install Redis server
2. Install PHP Redis extension
3. Update `.env`:
   ```
   CACHE_STORE=redis
   SESSION_DRIVER=redis
   ```

## Cached Endpoints

### LookupController
- **gradeLevels**: Cached for 1 hour (3600 seconds)
- **subjects**: Cached for 1 hour (3600 seconds)
- **academicYears**: Cached for 1 hour (3600 seconds)
- **terms**: Cached for 1 hour (3600 seconds), with per-academic-year keys

### DashboardController
- **today**: Cached for 5 minutes (300 seconds), per user per day

### StudentController
- **index**: Cached for 1 minute (60 seconds), based on query parameters

## Cache Invalidation

Models automatically clear related cache when data changes:

### GradeLevel Model
- Clears `grade_levels` cache on save/delete

### Subject Model
- Clears `subjects` cache on save/delete

### AcademicYear Model
- Clears `academic_years` cache on save/delete

### Term Model
- Clears `terms_{academic_year_id}` and `terms_all` cache on save/delete

### Student Model
- Clears all cache on save/delete (due to complex query dependencies)

## Cache Helper Service

A `CacheHelper` service is available at `App\Services\CacheHelper` with static methods:

```php
use App\Services\CacheHelper;

// Clear all cache
CacheHelper::clearAll();

// Clear lookup data only
CacheHelper::clearLookups();

// Clear dashboard for specific user
CacheHelper::clearDashboard($userId, $date);

// Clear student listings
CacheHelper::clearStudents();

// Clear terms for specific academic year
CacheHelper::clearTerms($academicYearId);
```

## Cache Keys

Common cache key patterns:
- `grade_levels` - All grade levels
- `subjects` - All subjects
- `academic_years` - All academic years
- `terms_{academic_year_id}` - Terms for specific year
- `terms_all` - All terms
- `dashboard_summary_{user_id}_{date}` - Dashboard summary
- `students_{hash}` - Student listings (hash based on query params)

## Future Improvements

1. **Switch to Redis**: For better performance in production
2. **Cache Tags**: Use cache tags for more granular invalidation
3. **Query Result Caching**: Add caching to more heavy queries
4. **Cache Warming**: Implement cache warming on deployment
5. **Cache Monitoring**: Add cache hit/miss metrics

## Testing Cache

To verify caching is working:

```php
// In a controller or route
use Illuminate\Support\Facades\Cache;

// Check if a value is cached
if (Cache::has('grade_levels')) {
    // Value is cached
}

// Clear cache for testing
Cache::flush();
```
