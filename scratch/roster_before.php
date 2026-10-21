<?php
use App\Models\{ClassAssignment, Student};
$out = [];
foreach (ClassAssignment::all() as $ca) {
    $out[$ca->id] = Student::where('status', 'active')
        ->whereHas('enrollments', fn ($q) => $q->where('section_id', $ca->section_id)->where('academic_year_id', $ca->academic_year_id))
        ->orderBy('id')->pluck('id')->all();
}
file_put_contents(base_path('scratch/roster_before.json'), json_encode($out));
echo json_encode($out), "\n";
