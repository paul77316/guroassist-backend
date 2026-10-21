<?php 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema; 

return new class extends Migration {
    public function up(): void {
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. "Mabini"
            $table->foreignId('grade_level_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('adviser_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->timestamps();
            $table->index(['grade_level_id', 'academic_year_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('sections');
    }
};