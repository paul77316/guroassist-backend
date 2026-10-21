<?php
use App\Models\{AuditLog, Student};
Student::withTrashed()->whereIn('student_id', ['TEST-001', 'TEST-EXIST'])->get()->each->forceDelete();
AuditLog::query()->delete();
@unlink(base_path('scratch/import_test.csv'));
echo "TEST rows left (expect 0): ", Student::withTrashed()->where('student_id', 'like', 'TEST-%')->count(), "\n";
