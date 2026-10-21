<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // GuroFlow does not compute official grades (the DepEd ECR does). Backup: scratch/grading_backup_*.sql
    public function up(): void
    {
        DB::table('audit_logs')->whereIn('auditable_type', [
            'App\\Models\\Assessment', 'App\\Models\\AssessmentScore',
            'App\\Models\\GradingConfiguration', 'App\\Models\\TermGrade',
        ])->delete();

        // child tables first (foreign keys)
        Schema::dropIfExists('term_grades');
        Schema::dropIfExists('assessment_scores');
        Schema::dropIfExists('assessments');
        Schema::dropIfExists('grading_components');
        Schema::dropIfExists('grading_configurations');
    }

    public function down(): void
    {
        // Intentionally empty. To restore the old data: mysql -u homestead -p guro_assist < scratch/grading_backup_*.sql
    }
};