<?php

namespace App\Providers;

use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\ClassAssignment;
use App\Models\Competency;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ExamScanBatch;
use App\Models\ExamSet;
use App\Models\GradeLevel;
use App\Models\Intervention;
use App\Models\ParentCommunication;
use App\Models\Student;
use App\Models\StudentNote;
use App\Models\Subject;
use App\Models\Term;
use App\Policies\AssignmentPolicy;
use App\Policies\ClassAssignmentPolicy;
use App\Policies\CompetencyPolicy;
use App\Policies\ExamPolicy;
use App\Policies\ExamQuestionPolicy;
use App\Policies\ExamScanBatchPolicy;
use App\Policies\ExamSetPolicy;
use App\Policies\InterventionPolicy;
use App\Policies\ParentCommunicationPolicy;
use App\Policies\SetupPolicy;
use App\Policies\StudentNotePolicy;
use App\Policies\StudentPolicy;
use App\Policies\TermPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        Gate::policy(ClassAssignment::class, ClassAssignmentPolicy::class);
        Gate::policy(Term::class, TermPolicy::class);
        Gate::policy(AcademicYear::class, SetupPolicy::class);
        Gate::policy(GradeLevel::class, SetupPolicy::class);
        Gate::policy(Subject::class, SetupPolicy::class);
        Gate::policy(Assignment::class, AssignmentPolicy::class);
        Gate::policy(Intervention::class, InterventionPolicy::class);
        Gate::policy(StudentNote::class, StudentNotePolicy::class);
        Gate::policy(ParentCommunication::class, ParentCommunicationPolicy::class);
        Gate::policy(Exam::class, ExamPolicy::class);
        Gate::policy(ExamSet::class, ExamSetPolicy::class);
        Gate::policy(ExamQuestion::class, ExamQuestionPolicy::class);
        Gate::policy(Competency::class, CompetencyPolicy::class);
        Gate::policy(ExamScanBatch::class, ExamScanBatchPolicy::class);
    }
}
