<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intervention_followups', function (Blueprint $table) {
            // Set only on a "completed" entry, so the history keeps the outcome even after a reopen clears it.
            // Older entries stay empty.
            $table->string('outcome', 30)->nullable()->after('notes'); // improved | partly_improved | no_change | not_yet_known
        });
    }

    public function down(): void
    {
        Schema::table('intervention_followups', function (Blueprint $table) {
            $table->dropColumn('outcome');
        });
    }
};
