<?php 
namespace App\Http\Controllers\Api; 
use App\Http\Controllers\Controller; 
use App\Http\Resources\SectionResource;
use App\Models\Section; 
use Illuminate\Http\Request; 

class SectionController extends Controller { 
    // Read-only for now — just enough for filters and the student form's
    // section dropdown. Full section management (create/edit) is a
    // later-phase admin feature, not needed yet.
    public function index(Request $request) {
        $user = $request->user();
        $query = Section::query()->with(['gradeLevel', 'academicYear']);
        if (! $user->isAdmin()) {
            $sectionIds = $user->teacher?->classAssignments()->pluck('section_id') ?? collect();
            $query->whereIn('id', $sectionIds);
        }
        return SectionResource::collection($query->get());
    }
}