<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Student; 
use App\Policies\StudentPolicy;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
         Gate::policy(Student::class, StudentPolicy::class);
         Gate::policy(\App\Models\ClassAssignment::class, \App\Policies\ClassAssignmentPolicy::class);
         Gate::policy(\App\Models\Term::class, \App\Policies\TermPolicy::class);
         Gate::policy(\App\Models\AcademicYear::class, \App\Policies\SetupPolicy::class);
         Gate::policy(\App\Models\GradeLevel::class, \App\Policies\SetupPolicy::class);
         Gate::policy(\App\Models\Subject::class, \App\Policies\SetupPolicy::class);
         Gate::policy(\App\Models\Assignment::class, \App\Policies\AssignmentPolicy::class);
         Gate::policy(\App\Models\Intervention::class, \App\Policies\InterventionPolicy::class);
         Gate::policy(\App\Models\StudentNote::class, \App\Policies\StudentNotePolicy::class);
         Gate::policy(\App\Models\ParentCommunication::class, \App\Policies\ParentCommunicationPolicy::class);
         Gate::policy(\App\Models\Exam::class, \App\Policies\ExamPolicy::class);
         Gate::policy(\App\Models\ExamSet::class, \App\Policies\ExamSetPolicy::class);
         Gate::policy(\App\Models\ExamQuestion::class, \App\Policies\ExamQuestionPolicy::class);
         Gate::policy(\App\Models\Competency::class, \App\Policies\CompetencyPolicy::class);
    }
}
