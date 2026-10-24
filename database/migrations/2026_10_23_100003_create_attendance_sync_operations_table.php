<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Receipts for operations the server committed or parked as conflicts. Rejected operations
        // are not stored: nothing was committed, so a retry is judged again from scratch.
        Schema::create('attendance_sync_operations', function (Blueprint $table) {
            $table->id();
            $table->uuid('operation_id')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('class_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('attendance_date');
            $table->enum('status', ['present', 'absent', 'late', 'excused']);
            $table->string('remarks')->nullable();
            $table->unsignedInteger('base_version');
            $table->char('payload_hash', 64);
            $table->enum('outcome', ['applied', 'conflict', 'discarded']);
            $table->unsignedInteger('server_version')->nullable();
            $table->string('resolution', 20)->nullable(); // keep_local | use_server
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'outcome']);
            $table->index(['class_assignment_id', 'attendance_date'], 'att_sync_ops_class_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_sync_operations');
    }
};
