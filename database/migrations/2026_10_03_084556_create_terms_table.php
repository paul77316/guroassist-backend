<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->string('name');                       // "Term 1"
            $table->unsignedTinyInteger('sort_order');    // 1, 2, 3... (count is not fixed)
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('upcoming'); // upcoming | active | completed
            $table->timestamps();

            $table->unique(['academic_year_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terms');
    }
};