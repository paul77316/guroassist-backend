<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('class_assignment_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->text('description')->nullable();
            $t->date('assigned_date');
            $t->date('due_date');
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();

            $t->index(['class_assignment_id', 'due_date']);
        });

        // Only exceptions to the default are stored: a student with no row is "not yet submitted"
        // before the due date and "missing" after it. Rows are submitted | late | excused.
        Schema::create('assignment_submissions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $t->foreignId('student_id')->constrained()->cascadeOnDelete();
            $t->string('status', 20);
            $t->date('submitted_date')->nullable();
            $t->string('feedback', 1000)->nullable();   // the teacher's own note about this submission
            $t->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();

            $t->unique(['assignment_id', 'student_id']);
            $t->index('student_id');
        });
    }

    public function down(): void
    {
        // Intentionally empty. Restore with: mysql -u homestead -p guro_assist < scratch/full_backup_*.sql
    }
};
