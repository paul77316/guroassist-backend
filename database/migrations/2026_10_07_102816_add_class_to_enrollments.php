<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. New column, plus a temporary index so the student_id foreign key
        //    survives dropping unique_enrollment (MySQL refuses otherwise).
        Schema::table('enrollments', function (Blueprint $t) {
            $t->foreignId('class_assignment_id')->nullable()->after('student_id')
                ->constrained('class_assignments')->cascadeOnDelete();
            $t->index('student_id', 'enrollments_student_tmp_index');
        });

        // 2. A student may now be in several classes of the same section.
        Schema::table('enrollments', function (Blueprint $t) {
            $t->dropUnique('unique_enrollment');
        });

        // 3. Backfill: one enrollment per student per matching (non-archived) class.
        foreach (DB::table('enrollments')->whereNull('class_assignment_id')->get() as $row) {
            $classIds = DB::table('class_assignments')
                ->where('section_id', $row->section_id)
                ->where('academic_year_id', $row->academic_year_id)
                ->whereNull('deleted_at')
                ->pluck('id');

            if ($classIds->isEmpty()) {
                continue; // no class for this section yet: stays unlinked (reported by the check script)
            }

            DB::table('enrollments')->where('id', $row->id)->update(['class_assignment_id' => $classIds->first()]);

            foreach ($classIds->slice(1) as $classId) {
                DB::table('enrollments')->insert([
                    'student_id' => $row->student_id,
                    'class_assignment_id' => $classId,
                    'section_id' => $row->section_id,
                    'academic_year_id' => $row->academic_year_id,
                    'created_at' => $row->created_at,
                    'updated_at' => now(),
                ]);
            }
        }

        // 4. One enrollment per student per class (NULLs are allowed more than once).
        Schema::table('enrollments', function (Blueprint $t) {
            $t->unique(['student_id', 'class_assignment_id'], 'enrollments_student_class_unique');
            $t->dropIndex('enrollments_student_tmp_index');
        });
    }

    public function down(): void
    {
        // Intentionally empty. Restore with: mysql -u homestead -p guro_assist < scratch/full_backup_*.sql
    }
};