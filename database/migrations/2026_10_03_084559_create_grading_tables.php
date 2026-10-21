<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grading_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('grade_level_id')->constrained()->restrictOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->foreignId('term_id')->nullable()->constrained('terms')->restrictOnDelete(); // null = all terms
            $table->string('name');
            $table->string('status', 20)->default('draft'); // draft | active | archived
            $table->decimal('passing_grade', 5, 2)->default(75);
            $table->string('category_average_method', 30)->default('total_points'); // total_points | mean_of_percentages
            $table->boolean('use_transmutation')->default(false);
            $table->unsignedTinyInteger('rounding_decimals')->default(2);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(
                ['academic_year_id', 'grade_level_id', 'subject_id', 'term_id', 'status'],
                'grading_config_lookup'
            );
        });

        Schema::create('grading_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grading_configuration_id')->constrained()->cascadeOnDelete();
            $table->string('name');                 // "Written Assessments"
            $table->string('type', 50);             // written | performance_task | summative | term_exam | ...
            $table->decimal('weight', 5, 2);        // percent
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->restrictOnDelete();
            $table->foreignId('grading_component_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('assessment_date')->nullable();
            $table->decimal('total_items', 6, 2);
            $table->string('status', 20)->default('active'); // active | archived
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['class_assignment_id', 'term_id']);
        });

        Schema::create('assessment_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->decimal('raw_score', 6, 2)->nullable();        // NULL = missing
            $table->decimal('percentage_score', 5, 2)->nullable(); // computed on save
            $table->string('remarks')->nullable();
            $table->timestamps();

            $table->unique(['assessment_id', 'student_id']);
            $table->index('student_id');
        });

        // Snapshot of a finalized term grade (used from checkpoint 5)
        Schema::create('term_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained('terms')->restrictOnDelete();
            $table->foreignId('grading_configuration_id')->constrained()->restrictOnDelete();
            $table->decimal('grade', 5, 2)->nullable();
            $table->boolean('is_provisional')->default(false);
            $table->json('breakdown')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'class_assignment_id', 'term_id'], 'term_grades_unique');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->morphs('auditable');
            $table->string('event', 50);            // created | updated | deleted | finalized
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('term_grades');
        Schema::dropIfExists('assessment_scores');
        Schema::dropIfExists('assessments');
        Schema::dropIfExists('grading_components');
        Schema::dropIfExists('grading_configurations');
    }
};