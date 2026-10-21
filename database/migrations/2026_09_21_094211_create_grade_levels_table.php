<?php 
use Illuminate\Database\Migrations\Migration; 
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema; 

return new class extends Migration {
    public function up(): void {
        Schema::create('grade_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. "Grade 7"
            $table->unsignedTinyInteger('order'); // 7, 8, 9, 10 for sorting
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('grade_levels');
    }
};