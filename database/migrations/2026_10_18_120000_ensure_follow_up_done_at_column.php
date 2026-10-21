<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The earlier migration may have run while its up() was empty, so add the column only if missing.
        if (! Schema::hasColumn('parent_communications', 'follow_up_done_at')) {
            Schema::table('parent_communications', function (Blueprint $table) {
                $table->date('follow_up_done_at')->nullable()->after('follow_up_date');
            });
        }
    }

    public function down(): void
    {
        // Intentionally empty: the earlier migration owns dropping this column.
    }
};