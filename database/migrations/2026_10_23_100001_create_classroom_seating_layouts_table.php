<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One current layout per class. Seats only help identify students: attendance rows never
        // reference a seat, so changing the layout cannot change attendance history.
        Schema::create('classroom_seating_layouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_assignment_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rows');
            $table->unsignedTinyInteger('cols');
            $table->json('cells'); // row-major list of student ids or null, length rows * cols
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classroom_seating_layouts');
    }
};
