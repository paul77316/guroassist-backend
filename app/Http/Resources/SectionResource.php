<?php 
namespace App\Http\Resources; 
use Illuminate\Http\Request; 
use Illuminate\Http\Resources\Json\JsonResource; 

class SectionResource extends JsonResource {
    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'grade_level' => $this->whenLoaded('gradeLevel', fn () => $this->gradeLevel->name),
            'academic_year' => $this->whenLoaded('academicYear', fn () => $this->academicYear->name),
            'sections' => $this->whenLoaded('sections', fn () => $this->sections->unique('id')->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values()),
        ];
    }
}