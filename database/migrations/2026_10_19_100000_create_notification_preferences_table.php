<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per teacher. Only two switches: no counts, names or text are stored here.
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->unique()->constrained('teachers')->restrictOnDelete();
            $table->boolean('in_app_enabled')->default(false);              // the reminder bell; off until the teacher opts in
            $table->boolean('include_students_to_review')->default(false);  // also count students to review (heavier)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
