<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentNote;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/** Private teacher notes. The note text is never written to the audit log. */
class StudentNoteService
{
    public function __construct(private StudentRecordGuard $guard) {}

    public function create(User $user, Student $student, array $data): StudentNote
    {
        $classId = ! empty($data['class_assignment_id']) ? (int) $data['class_assignment_id'] : null;
        $this->guard->assertContext($user, $student, $classId);

        return DB::transaction(function () use ($user, $student, $data, $classId) {
            $n = new StudentNote(['body' => $data['body']]);
            $n->student_id = $student->id;
            $n->class_assignment_id = $classId;
            $n->author_id = $user->id;
            $n->save();

            AuditLogger::log($user, $n, 'created', null, $this->values($n));

            return $n;
        });
    }

    public function update(User $user, StudentNote $n, array $data): StudentNote
    {
        $changed = $data['body'] !== $n->body;

        return DB::transaction(function () use ($user, $n, $data, $changed) {
            $n->update(['body' => $data['body']]);
            $event = $user->id === $n->author_id ? 'updated' : 'admin_corrected';
            AuditLogger::log($user, $n, $event, $this->values($n), $this->values($n) + ['body_changed' => $changed]);

            return $n;
        });
    }

    public function delete(User $user, StudentNote $n): void
    {
        DB::transaction(function () use ($user, $n) {
            AuditLogger::log($user, $n, 'deleted', $this->values($n), null);
            $n->delete();
        });
    }

    public function restore(User $user, StudentNote $n): void
    {
        DB::transaction(function () use ($user, $n) {
            $n->restore();
            AuditLogger::log($user, $n, 'restored', null, $this->values($n));
        });
    }

    public function listForStudent(User $user, Student $student, ?int $classId = null, bool $trashedOnly = false): Collection
    {
        Gate::forUser($user)->authorize('view', $student);

        $rows = ($trashedOnly ? StudentNote::onlyTrashed() : StudentNote::query())
            ->where('student_id', $student->id)
            ->inActiveClasses()
            ->when($classId, fn ($q) => $q->where('class_assignment_id', $classId))
            ->when(! $user->isAdmin(), fn ($q) => $q->authoredBy($user->id))
            ->orderByDesc('created_at')->orderByDesc('id')
            ->get();

        $this->guard->auditAdminRead($user, $student, 'student_note', $rows->pluck('id')->all());

        return $rows;
    }

    private function values(StudentNote $n): array
    {
        return ['student_id' => $n->student_id, 'class_assignment_id' => $n->class_assignment_id];
    }
}
