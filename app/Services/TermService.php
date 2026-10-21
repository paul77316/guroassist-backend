<?php

namespace App\Services;

use App\Models\Term;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TermService
{
    public function create(array $data, User $user): Term
    {
        $this->assertNoOverlap((int) $data['academic_year_id'], $data['start_date'], $data['end_date'], null);

        return DB::transaction(function () use ($data, $user) {
            $term = Term::create(Arr::only($data, [
                'academic_year_id', 'name', 'sort_order', 'start_date', 'end_date', 'status',
            ]));
            AuditLogger::log($user, $term, 'created', null, $this->values($term));

            return $term;
        });
    }

    public function update(Term $term, array $data, User $user): Term
    {
        $this->assertNoOverlap($term->academic_year_id, $data['start_date'], $data['end_date'], $term->id);
        $old = $this->values($term);

        return DB::transaction(function () use ($term, $data, $user, $old) {
            $term->update(Arr::only($data, ['name', 'sort_order', 'start_date', 'end_date', 'status']));
            AuditLogger::log($user, $term, 'updated', $old, $this->values($term->refresh()));

            return $term;
        });
    }

    public function delete(Term $term, User $user): void
    {
        // NOTE: when Assignments (Phase 5) start referencing terms, add an "in use" check here.
        DB::transaction(function () use ($term, $user) {
            AuditLogger::log($user, $term, 'deleted', $this->values($term), null);
            $term->delete();
        });
    }

    private function assertNoOverlap(int $yearId, string $start, string $end, ?int $ignoreId): void
    {
        $overlaps = Term::where('academic_year_id', $yearId)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->exists();

        if ($overlaps) {
            throw ValidationException::withMessages([
                'start_date' => ['These dates overlap another term in the same academic year.'],
            ]);
        }
    }

    private function values(Term $t): array
    {
        return [
            'name' => $t->name,
            'sort_order' => $t->sort_order,
            'start_date' => $t->start_date->format('Y-m-d'),
            'end_date' => $t->end_date->format('Y-m-d'),
            'status' => $t->status,
        ];
    }
}