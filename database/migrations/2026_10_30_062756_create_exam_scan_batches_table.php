<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_scan_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_set_id')->constrained('exam_sets')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->uuid('client_batch_id')->unique();
            $table->char('payload_hash', 64);
            $table->unsignedSmallInteger('sheet_count');
            $table->unsignedInteger('reviewed_mark_count')->default(0);
            $table->boolean('duplicate_check_confirmed');
            $table->timestamps();
            $table->index(['exam_set_id', 'created_at']);
        });

        Schema::create('exam_scan_question_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_scan_batch_id')->constrained('exam_scan_batches')->cascadeOnDelete();
            $table->foreignId('exam_question_id')->constrained('exam_questions')->restrictOnDelete();
            $table->json('choice_counts');
            $table->unsignedSmallInteger('blank_count')->default(0);
            $table->unsignedSmallInteger('multiple_count')->default(0);
            $table->timestamps();
            $table->unique(['exam_scan_batch_id', 'exam_question_id'], 'scan_batch_question_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_scan_question_counts');
        Schema::dropIfExists('exam_scan_batches');
    }
};
