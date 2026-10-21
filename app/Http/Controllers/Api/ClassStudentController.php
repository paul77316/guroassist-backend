<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentResource;
use App\Models\ClassAssignment;
use App\Models\Student;
use App\Services\EnrollmentService;
use App\Services\RosterImportService;
use Illuminate\Http\Request;
use App\Services\AuditLogger;

class ClassStudentController extends Controller
{
    public function __construct(private EnrollmentService $service) {}

    public function index(ClassAssignment $classAssignment)
    {
        $this->authorize('view', $classAssignment);

        return StudentResource::collection($this->service->roster($classAssignment));
    }

    public function candidates(Request $request, ClassAssignment $classAssignment)
    {
        $this->authorize('view', $classAssignment);

        return StudentResource::collection(
            $this->service->candidates($classAssignment, $request->user(), $request->query('search'))
        );
    }

    public function store(Request $request, ClassAssignment $classAssignment)
    {
        $this->authorize('view', $classAssignment);

        $data = $request->validate([
            'student_ids' => ['required_without:copy_from_class_assignment_id', 'array', 'min:1', 'max:200'],
            'student_ids.*' => ['integer'],
            'copy_from_class_assignment_id' => ['required_without:student_ids', 'integer', 'exists:class_assignments,id'],
        ]);

        if (! empty($data['copy_from_class_assignment_id'])) {
            $source = ClassAssignment::findOrFail($data['copy_from_class_assignment_id']);
            $this->authorize('view', $source); // can only copy a class you own
            $added = $this->service->copyFrom($classAssignment, $source, $request->user());
        } else {
            $added = $this->service->enrollVisible($classAssignment, $data['student_ids'], $request->user());
        }

        return StudentResource::collection($this->service->roster($classAssignment))
            ->additional(['added' => $added]);
    }

    public function destroy(Request $request, ClassAssignment $classAssignment, Student $student)
    {
        $this->authorize('view', $classAssignment);
        $this->service->remove($classAssignment, $student, $request->user());

        return response()->noContent();
    }

    /** confirm=0 returns a preview and changes nothing; confirm=1 saves the valid rows. */
    public function import(Request $request, ClassAssignment $classAssignment, RosterImportService $importer)
    {
        $this->authorize('view', $classAssignment);
        $this->authorize('create', Student::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:1024'],
            'confirm' => ['sometimes', 'boolean'],
        ]);

        $plan = $importer->plan($classAssignment, $request->file('file'), $request->user());

        $result = $request->boolean('confirm')
            ? $importer->commit($classAssignment, $plan, $request->user())
            : $importer->summarize($plan);

        return response()->json(['data' => $result]);
    }

   /** Full student details, in the same columns as the import template (so a roster can be re-imported). */
    public function export(Request $request, ClassAssignment $classAssignment)
    {
        $this->authorize('view', $classAssignment);
        $students = $this->service->roster($classAssignment);
        $filename = 'roster-class-' . $classAssignment->id . '-' . now()->format('Ymd') . '.csv';

        // Exports contain personal data, so record who exported which class (no student details in the log).
        AuditLogger::log($request->user(), $classAssignment, 'exported', null, ['students' => $students->count()]);

        return response()->streamDownload(function () use ($students) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM so Excel reads UTF-8 names correctly
            fputcsv($out, ['student_id', 'first_name', 'middle_name', 'last_name', 'sex', 'date_of_birth', 'contact_number', 'guardian_name', 'guardian_contact']);
            foreach ($students as $s) {
                fputcsv($out, array_map(fn ($v) => $this->csvSafe($v), [
                    $s->student_id,
                    $s->first_name,
                    $s->middle_name,
                    $s->last_name,
                    $s->sex,
                    $s->date_of_birth?->format('Y-m-d'),
                    $s->contact_number,
                    $s->guardian_name,
                    $s->guardian_contact,
                    $s->guardian_email,
                ]));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function template()
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['student_id', 'first_name', 'middle_name', 'last_name', 'sex', 'date_of_birth', 'contact_number', 'guardian_name', 'guardian_contact', 'guardian_email']);
            fclose($out);
        }, 'student-import-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Stops spreadsheets from running a cell that starts with = + - @ as a formula. */
    private function csvSafe(mixed $v): string
    {
        $v = (string) $v;

        return preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v;
    }
}