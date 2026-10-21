<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A teacher's own competency labels. Questions are tagged with one.
        Schema::create('competencies', function (Blueprint $t) {
            $t->id();
            $t->foreignId('owner_id')->constrained('users');
            $t->string('name', 120);
            $t->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $t->timestamps();
            $t->index(['owner_id', 'name']);
        });

        Schema::create('exams', function (Blueprint $t) {
            $t->id();
            $t->foreignId('author_id')->constrained('users');
            $t->foreignId('class_assignment_id')->constrained('class_assignments');
            $t->string('title', 160);
            $t->date('exam_date');
            $t->unsignedTinyInteger('choice_count'); // 4 (A-D) or 5 (A-E)
            $t->timestamps();
            $t->softDeletes();
            $t->index(['author_id', 'exam_date']);
            $t->index('class_assignment_id');
        });

        // One set = one printed version of the exam with its own questions and answer key.
        Schema::create('exam_sets', function (Blueprint $t) {
            $t->id();
            $t->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $t->string('label', 4);
            $t->string('qr_token', 64)->unique(); // opaque; never holds a name or an id number
            $t->timestamps();
            $t->unique(['exam_id', 'label']);
        });

        Schema::create('exam_questions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('exam_set_id')->constrained('exam_sets')->cascadeOnDelete();
            $t->unsignedSmallInteger('position');
            $t->text('body');
            $t->json('choices');
            $t->string('correct_choice', 1);
            $t->foreignId('competency_id')->constrained('competencies');
            $t->string('image_path', 255)->nullable();
            $t->string('image_alt', 255)->nullable();
            $t->timestamps();
            $t->index(['exam_set_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_questions');
        Schema::dropIfExists('exam_sets');
        Schema::dropIfExists('exams');
        Schema::dropIfExists('competencies');
    }
};