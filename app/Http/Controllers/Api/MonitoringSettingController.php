<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateMonitoringSettingsRequest;
use App\Models\MonitoringSetting;
use App\Services\AttendanceSummaryService;
use Illuminate\Http\Request;

class MonitoringSettingController extends Controller
{
    public function __construct(private AttendanceSummaryService $service) {}

    public function show(Request $request)
    {
        $teacher = $request->user()->teacher;
        if (! $teacher) {
            return response()->json(['message' => 'Only teachers have monitoring settings.'], 403);
        }

        return response()->json(['data' => $this->service->settingsForTeacher($teacher->id)]);
    }

    public function update(UpdateMonitoringSettingsRequest $request)
    {
        $teacher = $request->user()->teacher;
        if (! $teacher) {
            return response()->json(['message' => 'Only teachers have monitoring settings.'], 403);
        }

        MonitoringSetting::updateOrCreate(['teacher_id' => $teacher->id], $request->validated());

        return response()->json(['data' => $this->service->settingsForTeacher($teacher->id)]);
    }
}
