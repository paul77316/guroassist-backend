<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait HandlesStudentRecords
{
    /**
     * Eager-load what the resources need so queries stay flat as records grow.
     * Works for one model or a collection. Admins also get the author.
     */
    protected function withContext(Model|Collection $records, Request $request, array $extra = []): Model|Collection
    {
        $relations = ['classAssignment.section.gradeLevel', 'classAssignment.subject'];
        if ($request->user()->isAdmin()) {
            $relations[] = 'author';
        }

        return $records->load(array_merge($relations, $extra));
    }

    protected function notDeletedResponse(string $what)
    {
        $msg = "This {$what} is not deleted.";

        return response()->json(['message' => $msg, 'errors' => ['record' => [$msg]]], 422);
    }
}
