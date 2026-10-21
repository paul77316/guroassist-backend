<?php 
use Illuminate\Database\Migrations\Migration; 
use Illuminate\Database\Schema\Blueprint; 
use Illuminate\Support\Facades\Schema; 

return new class extends Migration { 
    public function up(): void {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            // A student can only be enrolled once per section per year
            $table->unique(['student_id', 'section_id', 'academic_year_id'], 'unique_enrollment');
            $table->index(['section_id', 'academic_year_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('enrollments');
    }
};