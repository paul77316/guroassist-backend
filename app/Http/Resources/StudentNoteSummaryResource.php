<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\PresentsRecords;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/** List row: a short preview only. The full note is in the detail endpoint. */
class StudentNoteSummaryResource extends JsonResource
{
    use PresentsRecords;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'class' => $this->classInfo(),
            'preview' => Str::limit((string) $this->body, 100),
            'date' => $this->created_at?->copy()->timezone(config('monitoring.timezone'))->toDateString(),
            'deleted' => $this->trashed(),
            'author' => $this->authorName($request),
        ];
    }
}
