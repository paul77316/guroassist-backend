<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Database\Seeder;

class TermSeeder extends Seeder
{
    public function run(): void
    {
        // DEMO DATA: placeholder dates, not official DepEd calendar dates.
        $year = AcademicYear::first();

        $terms = [
            [1, 'Term 1', '2026-06-08', '2026-10-16', Term::ACTIVE],
            [2, 'Term 2', '2026-10-19', '2027-02-12', Term::UPCOMING],
            [3, 'Term 3', '2027-02-15', '2027-04-02', Term::UPCOMING],
        ];

        foreach ($terms as [$order, $name, $start, $end, $status]) {
            Term::updateOrCreate(
                ['academic_year_id' => $year->id, 'sort_order' => $order],
                ['name' => $name, 'start_date' => $start, 'end_date' => $end, 'status' => $status]
            );
        }
    }
}