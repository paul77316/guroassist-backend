<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parent_communications', function (Blueprint $table) {
            // Stamped by the server (school-timezone date) when the teacher marks the follow-up done.
            $table->date('follow_up_done_at')->nullable()->after('follow_up_date');
        });
    }

    public function down(): void
    {
        Schema::table('parent_communications', function (Blueprint $table) {
            $table->dropColumn('follow_up_done_at');
        });
    }
};