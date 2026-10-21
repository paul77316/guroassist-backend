<?php

namespace App\Http\Resources;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\Subject;
use App\Services\SetupService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SetupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $item = $this->resource;
        $usage = app(SetupService::class)->usage($item);

        $out = [
            'id' => $item->id,
            'name' => $item->name,
            'usage' => $usage,
            'usage_total' => array_sum($usage),
        ];

        if ($item instanceof GradeLevel) {
            $out['order'] = $item->order;
        }
        if ($item instanceof Subject) {
            $out['code'] = $item->code;
        }
        if ($item instanceof AcademicYear) {
            $out['start_date'] = $item->start_date->format('Y-m-d');
            $out['end_date'] = $item->end_date->format('Y-m-d');
            $out['is_active'] = (bool) $item->is_active;
        }

        return $out;
    }
}