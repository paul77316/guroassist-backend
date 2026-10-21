<?php

namespace App\Services;

use App\Models\ClassAssignment;
use App\Models\Exam;
use App\Models\ExamSet;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Exams and their sets. An exam holds no student data.
 * Audit values hold ids, counts, dates and "text changed" flags, never titles or question text.
 */
class ExamService
{
    public const CHOICE_COUNTS = [3, 4, 5];
    public const MAX_SETS = 10;

    // ---------------------------------------------------------------- create / edit

    /** Creates the exam and its first set (A). Class, author and QR token are decided here, never by the client. */
    public function create(User $user, array $data): Exam
    {
        $ca = $this->assertClass($user, (int) ($data['class_assignment_id'] ?? 0));

        return DB::transaction(function () use ($user, $data, $ca) {
            $exam = new Exam(Arr::only($data, ['title', 'exam_date', 'choice_count']));
            $exam->author_id = $user->id;
            $exam->class_assignment_id = $ca->id;
            $exam->save();

            $set = $this->newSet($exam, 'A');

            AuditLogger::log($user, $exam, 'created', null, $this->values($exam) + ['set_id' => $set->id]);

            return $exam;
        });
    }

    /** Title, date and choice count. The class never changes. An admin editing someone else's exam is "admin_corrected". */
    public function update(User $user, Exam $exam, array $data): Exam
    {
        $payload = Arr::only($data, ['title', 'exam_date', 'choice_count']);

        if (isset($payload['choice_count'])
            && (int) $payload['choice_count'] !== (int) $exam->choice_count
            && $exam->questions()->exists()) {
            throw ValidationException::withMessages([
                'choice_count' => ['The number of choices cannot change once the exam has questions.'],
            ]);
        }

        $flags = ['title_changed' => array_key_exists('title', $payload) && $payload['title'] !== $exam->title];
        $old = $this->values($exam);

        return DB::transaction(function () use ($user, $exam, $payload, $old, $flags) {
            $exam->update($payload);

            $event = $user->id === $exam->author_id ? 'updated' : 'admin_corrected';
            AuditLogger::log($user, $exam, $event, $old, $this->values($exam->refresh()) + $flags);

            return $exam;
        });
    }

    public function delete(User $user, Exam $exam): void
    {
        DB::transaction(function () use ($user, $exam) {
            AuditLogger::log($user, $exam, 'deleted', $this->values($exam), null);
            $exam->delete();
        });
    }

    public function restore(User $user, Exam $exam): void
    {
        DB::transaction(function () use ($user, $exam) {
            $exam->restore();
            AuditLogger::log($user, $exam, 'restored', null, $this->values($exam));
        });
    }

    // ---------------------------------------------------------------- sets

    public function addSet(User $user, Exam $exam): ExamSet
    {
        return DB::transaction(function () use ($user, $exam) {
            // Lock the exam row so two requests cannot pick the same label.
            Exam::query()->lockForUpdate()->find($exam->id);

            $labels = ExamSet::query()->where('exam_id', $exam->id)->pluck('label')->all();
            if (count($labels) >= self::MAX_SETS) {
                throw ValidationException::withMessages(['exam' => ['An exam can have at most ' . self::MAX_SETS . ' sets.']]);
            }

            $next = collect(range('A', 'Z'))->first(fn ($l) => ! in_array($l, $labels, true));
            $set = $this->newSet($exam, $next);

            AuditLogger::log($user, $exam, 'set_added', null, ['set_id' => $set->id, 'label' => $set->label]);

            return $set;
        });
    }

    /** Removes a set and its questions. An exam keeps at least one set. */
    public function deleteSet(User $user, ExamSet $set): void
    {
        $paths = [];

        DB::transaction(function () use ($user, $set, &$paths) {
            $exam = Exam::query()->lockForUpdate()->find($set->exam_id);
            if (! $exam) {
                throw ValidationException::withMessages(['set' => ['This set is not available.']]);
            }
            if (ExamSet::query()->where('exam_id', $exam->id)->count() <= 1) {
                throw ValidationException::withMessages(['set' => ['An exam needs at least one set.']]);
            }

            $paths = $set->questions()->whereNotNull('image_path')->pluck('image_path')->all();

            AuditLogger::log($user, $exam, 'set_deleted', [
                'set_id' => $set->id,
                'label' => $set->label,
                'question_count' => $set->questions()->count(),
            ], null);

            $set->delete(); // the questions go with it (foreign key cascade)
        });

        if ($paths) {
            Storage::disk('local')->delete($paths);
        }
    }

    // ---------------------------------------------------------------- reading

    /**
     * Exams for the list. Teachers see their own; admins see all authors (one audit row per request).
     * Exams of an archived class are excluded for everyone. Rows carry counts, no question text.
     */
    public function list(User $user, ?int $classId = null, bool $trashedOnly = false): Collection
    {
        $rows = ($trashedOnly ? Exam::onlyTrashed() : Exam::query())
            ->inActiveClasses()
            ->when($classId, fn ($q) => $q->where('class_assignment_id', $classId))
            ->when(! $user->isAdmin(), fn ($q) => $q->authoredBy($user->id))
            ->withCount(['sets', 'questions'])
            ->orderByDesc('exam_date')->orderByDesc('id')
            ->get();

        $this->auditAdminRead($user, $user, $rows->pluck('id')->all());

        return $rows;
    }

    /** One audit row for an admin reading one exam or one of its sets. */
    public function auditRead(User $user, Exam $exam): void
    {
        $this->auditAdminRead($user, $exam, [$exam->id]);
    }

    // ---------------------------------------------------------------- internals

    private function auditAdminRead(User $user, Model $on, array $ids): void
    {
        if (! $user->isAdmin() || $ids === []) {
            return;
        }

        AuditLogger::log($user, $on, 'admin_read', null, [
            'record_type' => 'exam',
            'record_ids' => array_values($ids),
        ]);
    }

    /** The class must be active and the teacher's own. One message for "missing", "archived" and "not yours". */
    private function assertClass(User $user, int $classId): ClassAssignment
    {
        $ca = $classId > 0 ? ClassAssignment::query()->find($classId) : null;
        $mine = $ca && $user->teacher && $ca->teacher_id === $user->teacher->id;

        if (! $ca || $ca->status !== 'active' || ! $mine) {
            throw ValidationException::withMessages([
                'class_assignment_id' => ['This class is not available for new exams.'],
            ]);
        }

        return $ca;
    }

    private function newSet(Exam $exam, string $label): ExamSet
    {
        $set = new ExamSet(['label' => $label]);
        $set->exam_id = $exam->id;
        $set->qr_token = $this->freshToken();
        $set->save();

        return $set;
    }

    private function freshToken(): string
    {
        do {
            $token = Str::random(40);
        } while (ExamSet::query()->where('qr_token', $token)->exists());

        return $token;
    }

    private function values(Exam $exam): array
    {
        return [
            'class_assignment_id' => $exam->class_assignment_id,
            'exam_date' => $exam->exam_date?->format('Y-m-d'),
            'choice_count' => $exam->choice_count,
        ];
    }
}