<?php

namespace App\Services;

use App\Models\ClassAssignment;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RosterImportService
{
    public const MAX_ROWS = 500;

    private const FIELDS = [
        'student_id', 'first_name', 'middle_name', 'last_name', 'sex',
        'date_of_birth', 'contact_number', 'guardian_name', 'guardian_contact','guardian_email',
    ];

    private const ALIASES = [
        'lrn' => 'student_id', 'student_number' => 'student_id', 'studentid' => 'student_id',
        'firstname' => 'first_name', 'given_name' => 'first_name',
        'middlename' => 'middle_name',
        'lastname' => 'last_name', 'surname' => 'last_name',
        'gender' => 'sex',
        'birthdate' => 'date_of_birth', 'birth_date' => 'date_of_birth', 'dob' => 'date_of_birth',
        'contact' => 'contact_number', 'guardian' => 'guardian_name',
        'guardianemail' => 'guardian_email', 'parent_email' => 'guardian_email',
    ];

    public function __construct(private EnrollmentService $enrollments) {}

    /** Validate every row and decide what would happen. Changes nothing. */
    public function plan(ClassAssignment $ca, UploadedFile $file, User $user): array
    {
        if ($ca->status !== 'active') {
            throw ValidationException::withMessages(['file' => ['This class is archived.']]);
        }

        $plan = [];
        $seen = [];

        foreach ($this->readRows($file) as ['line' => $line, 'fields' => $r]) {
            $res = $this->analyze($r, $ca, $user, $seen, $line);
            $plan[] = $res + [
                'row' => $line,
                'student_id' => $r['student_id'],
                'name' => $res['student']?->full_name ?? trim($r['first_name'] . ' ' . $r['last_name']),
            ];
        }

        return $plan;
    }

    /** Save the valid rows. Rows with errors are skipped. */
    public function commit(ClassAssignment $ca, array $plan, User $user): array
    {
        $created = 0;
        $ids = [];

        $enrolled = DB::transaction(function () use ($plan, $ca, $user, &$created, &$ids) {
            foreach ($plan as $p) {
                if ($p['action'] === 'create') {
                    $student = Student::create($p['data']);
                    // IDs only in the audit log, never personal details
                    AuditLogger::log($user, $student, 'created', null, [
                        'student_id' => $student->student_id,
                        'source' => 'csv_import',
                        'class_assignment_id' => $ca->id,
                    ]);
                    $ids[] = $student->id;
                    $created++;
                } elseif ($p['action'] === 'enroll') {
                    $ids[] = $p['student']->id;
                }
            }

            return $ids ? $this->enrollments->enroll($ca, $ids, $user) : 0;
        });

        return $this->summarize($plan) + ['created' => $created, 'enrolled' => $enrolled];
    }

    public function summarize(array $plan): array
    {
        $counts = ['create' => 0, 'enroll' => 0, 'already' => 0, 'error' => 0];
        $rows = [];

        foreach ($plan as $p) {
            $counts[$p['action']]++;
            $rows[] = Arr::only($p, ['row', 'student_id', 'name', 'action', 'message']);
        }

        return ['counts' => $counts, 'rows' => $rows];
    }

    private function analyze(array $r, ClassAssignment $ca, User $user, array &$seen, int $line): array
    {
        $sid = $r['student_id'];

        if ($sid === '') return $this->result('error', 'Student ID is required.');
        if (mb_strlen($sid) > 50) return $this->result('error', 'Student ID is too long (max 50 characters).');
        if (isset($seen[$sid])) return $this->result('error', "Duplicate of row {$seen[$sid]} in this file.");
        $seen[$sid] = $line;

        $existing = Student::withTrashed()->where('student_id', $sid)->first();

        if ($existing) {
            if ($existing->trashed() || $existing->status !== 'active') {
                return $this->result('error', 'This student ID belongs to an archived student.');
            }
            if (Enrollment::where('class_assignment_id', $ca->id)->where('student_id', $existing->id)->exists()) {
                return $this->result('already', 'Already in this class.', null, $existing);
            }
            $visible = $this->enrollments->visibleQuery($ca, $user)->where('id', $existing->id)->exists();
            if (! $visible) {
                return $this->result('error', "This student ID already exists but isn't in one of your classes. Ask an admin to add them.");
            }
            if ($msg = $this->enrollments->levelConflict($existing, $ca)) {
                return $this->result('error', $msg);
            }

            return $this->result('enroll', 'Existing student will be added to this class.', null, $existing);
        }

        $data = $this->normalize($r);
        $v = Validator::make($data, [
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'sex' => ['required', 'in:male,female'],
            'date_of_birth' => ['required', 'date_format:Y-m-d', 'before:today'],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'guardian_name' => ['nullable', 'string', 'max:150'],
            'guardian_contact' => ['nullable', 'string', 'max:20'],
            'guardian_email' => ['nullable', 'email:rfc', 'max:255'],
        ], [
            'date_of_birth.date_format' => 'Date of birth must look like 2013-05-20 or 5/20/2013.',
            'sex.in' => 'Sex must be male or female.',
        ]);

        if ($v->fails()) {
            return $this->result('error', $v->errors()->first());
        }

        return $this->result('create', 'New student will be created and added.', $data + ['student_id' => $sid, 'status' => 'active']);
    }

    private function result(string $action, string $message, ?array $data = null, ?Student $student = null): array
    {
        return ['action' => $action, 'message' => $message, 'data' => $data, 'student' => $student];
    }

    private function normalize(array $r): array
    {
        $d = Arr::except($r, ['student_id']);
        $sex = strtolower($d['sex']);
        $d['sex'] = ['m' => 'male', 'f' => 'female'][$sex] ?? $sex;
        $d['date_of_birth'] = $this->parseDate($d['date_of_birth']) ?? $d['date_of_birth'];

        foreach (['middle_name', 'contact_number', 'guardian_name', 'guardian_contact', 'guardian_email'] as $k) {
            if ($d[$k] === '') $d[$k] = null;
        }
        if ($d['guardian_email'] !== null) $d['guardian_email'] = strtolower($d['guardian_email']);

        return $d;
    }

    private function parseDate(string $v): ?string
    {
        foreach (['Y-m-d', 'n/j/Y'] as $format) {
            $d = \DateTime::createFromFormat('!' . $format, $v);
            if ($d && $d->format($format) === $v) {
                return $d->format('Y-m-d');
            }
        }

        return null;
    }

    /** @return array<int, array{line:int, fields:array}> */
    private function readRows(UploadedFile $file): array
    {
        $h = fopen($file->getRealPath(), 'r');
        $first = $h ? fgets($h) : false;
        if ($first === false) {
            throw ValidationException::withMessages(['file' => ['The file is empty or unreadable.']]);
        }

        $first = $this->utf8(preg_replace('/^\xEF\xBB\xBF/', '', $first));
        $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
        $headers = array_map(fn ($c) => $this->headerKey($c), str_getcsv($first, $delimiter));

        if (! in_array('student_id', $headers, true)) {
            throw ValidationException::withMessages(['file' => ['The first row must be a header row that includes student_id. Download the template to see the format.']]);
        }

        $rows = [];
        $line = 1;
        while (($cols = fgetcsv($h, 0, $delimiter)) !== false) {
            $line++;
            if (count(array_filter($cols, fn ($c) => trim((string) $c) !== '')) === 0) {
                continue; // blank line
            }

            $fields = array_fill_keys(self::FIELDS, '');
            foreach ($headers as $i => $key) {
                if (in_array($key, self::FIELDS, true)) {
                    $value = trim($this->utf8((string) ($cols[$i] ?? '')));
                    $fields[$key] = preg_replace("/^'([=+\-@])/", '$1', $value); // undo the export's formula protection
                }
            }
            $rows[] = ['line' => $line, 'fields' => $fields];

            if (count($rows) > self::MAX_ROWS) {
                throw ValidationException::withMessages(['file' => ['Too many rows. The maximum is ' . self::MAX_ROWS . ' students per import.']]);
            }
        }
        fclose($h);

        if (! $rows) {
            throw ValidationException::withMessages(['file' => ['The file has a header row but no students.']]);
        }

        return $rows;
    }

    private function headerKey(?string $c): string
    {
        $k = strtolower(trim($this->utf8((string) $c)));
        $k = preg_replace('/[\s\-]+/', '_', $k);

        return self::ALIASES[$k] ?? $k;
    }

    private function utf8(string $s): string
    {
        return mb_check_encoding($s, 'UTF-8') ? $s : mb_convert_encoding($s, 'UTF-8', 'Windows-1252');
    }
}
