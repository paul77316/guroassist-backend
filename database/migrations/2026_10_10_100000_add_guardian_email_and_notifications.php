<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $t) {
            $t->string('guardian_email')->nullable()->after('guardian_contact');
        });

        // Opt-in per teacher: nothing is emailed until the teacher turns it on in Settings.
        Schema::table('monitoring_settings', function (Blueprint $t) {
            $t->boolean('guardian_email_enabled')->default(false);
            $t->boolean('notify_on_absent')->default(true);
            $t->boolean('notify_on_late')->default(false);
        });

        // One row per student, class, date and status, so the same notice is never sent twice.
        // The guardian's address is deliberately NOT stored here.
        Schema::create('attendance_notifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_id')->constrained()->cascadeOnDelete();
            $t->foreignId('class_assignment_id')->constrained()->cascadeOnDelete();
            $t->date('attendance_date');
            $t->string('status', 20);                  // absent | late
            $t->string('state', 20)->default('queued'); // queued | sent | skipped | failed
            $t->string('error', 100)->nullable();
            $t->timestamp('sent_at')->nullable();
            $t->timestamps();

            $t->unique(['student_id', 'class_assignment_id', 'attendance_date', 'status'], 'attendance_notice_unique');
        });
    }

    public function down(): void
    {
        // Intentionally empty. Restore with: mysql -u homestead -p guro_assist < scratch/full_backup_*.sql
    }
};
