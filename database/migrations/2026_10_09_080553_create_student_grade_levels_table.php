<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_grade_levels', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_id')->constrained()->cascadeOnDelete();
            $t->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $t->foreignId('grade_level_id')->constrained()->restrictOnDelete();
            $t->timestamps();

            $t->unique(['student_id', 'academic_year_id']);
        });

        // Backfill from existing enrollments: the grade level of the section they are enrolled in.
        $rows = DB::table('enrollments')
            ->join('sections', 'sections.id', '=', 'enrollments.section_id')
            ->orderBy('enrollments.id')
            ->get(['enrollments.student_id', 'enrollments.academic_year_id', 'sections.grade_level_id'])
            ->unique(fn ($r) => $r->student_id . '-' . $r->academic_year_id);

        foreach ($rows as $r) {
            DB::table('student_grade_levels')->insert([
                'student_id' => $r->student_id,
                'academic_year_id' => $r->academic_year_id,
                'grade_level_id' => $r->grade_level_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Intentionally empty. Restore with: mysql -u homestead -p guro_assist < scratch/full_backup_*.sql
    }
};