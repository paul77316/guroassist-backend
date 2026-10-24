<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing rows start at 1. A missing row counts as version 0 for conflict checks.
        Schema::table('attendance', function (Blueprint $table) {
            $table->unsignedInteger('sync_version')->default(1)->after('recorded_by');
        });
    }

    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropColumn('sync_version');
        });
    }
};
