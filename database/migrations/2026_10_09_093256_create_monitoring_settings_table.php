<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_settings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('teacher_id')->unique()->constrained()->cascadeOnDelete();
            $t->decimal('attendance_rate_threshold', 5, 2)->default(80);
            $t->unsignedTinyInteger('consecutive_absence_threshold')->default(3);
            $t->unsignedTinyInteger('min_days_for_rate')->default(5);
            $t->unsignedTinyInteger('missing_assignments_threshold')->default(3); // used by Assignments
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_settings');
    }
};