<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Private teacher notes. Visible only to the author (and admins).
        Schema::create('student_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
            $table->foreignId('class_assignment_id')->nullable()->constrained('class_assignments')->restrictOnDelete();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['student_id', 'created_at']);
            $table->index('author_id');
        });

        Schema::create('interventions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
            $table->foreignId('class_assignment_id')->nullable()->constrained('class_assignments')->restrictOnDelete();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->string('concern_type', 40);
            $table->text('concern');
            $table->string('intervention_type', 40);
            $table->date('intervention_date');
            $table->date('follow_up_date')->nullable();          // the active due date; cleared when Completed
            $table->string('status', 30)->default('planned');    // planned | in_progress | requires_follow_up | completed
            $table->string('outcome', 30)->nullable();           // improved | partly_improved | no_change | not_yet_known
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['author_id', 'status', 'follow_up_date']);
            $table->index('student_id');
        });

        // History of everything that happens to an intervention. Never soft-deleted, never edited.
        Schema::create('intervention_followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intervention_id')->constrained('interventions')->restrictOnDelete();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->string('entry_type', 20);                    // followup | rescheduled | completed | reopened
            $table->date('followup_date');                       // the day the entry is about (school timezone)
            $table->string('from_status', 30);
            $table->string('to_status', 30);
            $table->text('progress')->nullable();
            $table->text('notes')->nullable();                   // also holds the reopen reason
            $table->date('next_follow_up_date')->nullable();
            $table->timestamps();

            $table->index(['intervention_id', 'followup_date']);
        });

        Schema::create('parent_communications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
            $table->foreignId('class_assignment_id')->nullable()->constrained('class_assignments')->restrictOnDelete();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->date('contact_date');
            $table->string('method', 20);                        // phone_call | text | in_person | email | other
            $table->string('reason', 255);
            $table->text('summary');
            $table->date('follow_up_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['student_id', 'contact_date']);
            $table->index(['author_id', 'follow_up_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_communications');
        Schema::dropIfExists('intervention_followups');
        Schema::dropIfExists('interventions');
        Schema::dropIfExists('student_notes');
    }
};
