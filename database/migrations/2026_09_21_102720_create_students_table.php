<?php 
use Illuminate\Database\Migrations\Migration; 
use Illuminate\Database\Schema\Blueprint; 
use Illuminate\Support\Facades\Schema; 

return new class extends Migration { 
    public function up(): void { 
        Schema::create('students', function (Blueprint $table) { 
            $table->id(); 
            $table->string('student_id')->unique(); // school-issued ID, not the PK 
            $table->string('first_name'); 
            $table->string('middle_name')->nullable(); 
            $table->string('last_name'); 
            $table->enum('sex', ['male', 'female']); 
            $table->date('date_of_birth'); 
            $table->string('contact_number')->nullable(); 
            $table->string('guardian_name')->nullable(); 
            $table->string('guardian_contact')->nullable(); 
            $table->enum('status', ['active', 'archived', 'transferred'])->default('active'); 
            $table->timestamps(); 
            $table->softDeletes(); // supports the "Archive student" feature (\u00a75) without losing history 
            $table->index('student_id'); 
        }); 
    } 
    public function down(): void { 
        Schema::dropIfExists('students'); 
    } 
};