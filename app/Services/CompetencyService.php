<?php

namespace App\Services;

use App\Models\Competency;
use App\Models\ExamQuestion;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A teacher's personal competency list. The audit log holds ids and flags only, never the name.
 */
class CompetencyService
{
    public function list(User $user): Collection
    {
        return Competency::query()
            ->where('owner_id', $user->id)
            ->with('subject')
            ->withCount('questions')
            ->orderBy('name')
            ->get();
    }

    public function create(User $user, array $data): Competency
    {
        $name = trim((string) $data['name']);
        $subjectId = ! empty($data['subject_id']) ? (int) $data['subject_id'] : null;
        $this->assertUnique($user, $name, $subjectId);

        return DB::transaction(function () use ($user, $name, $subjectId) {
            $c = new Competency(['name' => $name, 'subject_id' => $subjectId]);
            $c->owner_id = $user->id;
            $c->save();

            AuditLogger::log($user, $c, 'created', null, $this->values($c));

            return $c;
        });
    }

    public function update(User $user, Competency $c, array $data): Competency
    {
        $name = trim((string) $data['name']);
        $subjectId = ! empty($data['subject_id']) ? (int) $data['subject_id'] : null;
        $this->assertUnique($user, $name, $subjectId, $c->id);

        $old = $this->values($c);
        $flags = ['name_changed' => $name !== $c->name];

        return DB::transaction(function () use ($user, $c, $name, $subjectId, $old, $flags) {
            $c->fill(['name' => $name, 'subject_id' => $subjectId])->save();
            AuditLogger::log($user, $c, 'updated', $old, $this->values($c->refresh()) + $flags);

            return $c;
        });
    }

    public function delete(User $user, Competency $c): void
    {
        if (ExamQuestion::query()->where('competency_id', $c->id)->exists()) {
            throw ValidationException::withMessages([
                'competency' => ['This competency is used by questions. Change those questions first.'],
            ]);
        }

        DB::transaction(function () use ($user, $c) {
            AuditLogger::log($user, $c, 'deleted', $this->values($c), null);
            $c->delete();
        });
    }

    private function assertUnique(User $user, string $name, ?int $subjectId, ?int $ignoreId = null): void
    {
        $exists = Competency::query()
            ->where('owner_id', $user->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->when($subjectId === null, fn ($q) => $q->whereNull('subject_id'), fn ($q) => $q->where('subject_id', $subjectId))
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['name' => ['You already have a competency with this name.']]);
        }
    }

    private function values(Competency $c): array
    {
        return ['owner_id' => $c->owner_id, 'subject_id' => $c->subject_id];
    }
}