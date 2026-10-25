<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('teacher_notification_sync_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_assignment_id')->constrained()->cascadeOnDelete();
            $table->date('attendance_date');
            $table->unsignedBigInteger('last_operation_id');
            $table->timestamps();
            $table->unique(['user_id', 'class_assignment_id', 'attendance_date'], 'teacher_sync_reads_group_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_notification_sync_reads');
    }
};
