<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateNotificationPreferencesRequest;
use App\Services\NotificationSummaryService;
use Illuminate\Http\Request;

/** In-app reminders. Counts only; see NotificationSummaryService. */
class NotificationController extends Controller
{
    public function __construct(private NotificationSummaryService $service) {}

    public function summary(Request $request)
    {
        return response()->json(['data' => $this->service->summary($request->user())]);
    }

    public function showPreferences(Request $request)
    {
        if (! $request->user()->teacher) {
            return response()->json(['message' => 'Only teachers have reminder settings.'], 403);
        }

        return response()->json(['data' => $this->service->preferencesFor($request->user())]);
    }

    public function updatePreferences(UpdateNotificationPreferencesRequest $request)
    {
        if (! $request->user()->teacher) {
            return response()->json(['message' => 'Only teachers have reminder settings.'], 403);
        }

        return response()->json(['data' => $this->service->savePreferences($request->user(), $request->validated())]);
    }
}
