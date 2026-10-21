<?php

namespace App\Models\Concerns;

use App\Models\ClassAssignment;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shared by the intervention-module models. Records of an archived class are hidden from everyone
 * (admins too). Archiving soft-deletes a class, so ClassAssignment's default scope already leaves
 * archived classes out; the status check is a second guard. Records with no class always show.
 * Nothing is deleted: restore the class and its records reappear.
 */
trait HidesArchivedClasses
{
    public function scopeInActiveClasses(Builder $query): Builder
    {
        $col = $this->qualifyColumn('class_assignment_id');

        return $query->where(function (Builder $q) use ($col) {
            $q->whereNull($col)->orWhereIn(
                $col,
                ClassAssignment::query()->where('status', 'active')->select('id')
            );
        });
    }

    public function scopeAuthoredBy(Builder $query, int $userId): Builder
    {
        return $query->where($this->qualifyColumn('author_id'), $userId);
    }
}
