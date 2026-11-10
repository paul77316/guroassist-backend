<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExamScanBatchRequest;
use App\Models\ExamScanBatch;
use App\Services\ExamScanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MobileExamScanController extends Controller
{
    public function __construct(private ExamScanService $service) {}

    public function showSet(Request $request, string $qr_token): JsonResponse
    {
        return response()->json(['data' => $this->service->setForScan($request->user(), $qr_token)]);
    }

    public function index(Request $request, string $qr_token): JsonResponse
    {
        return response()->json(['data' => $this->service->batchesForSet($request->user(), $qr_token)]);
    }

    public function store(StoreExamScanBatchRequest $request): JsonResponse
    {
        $batch = $this->service->createBatch($request->user(), $request->validated());

        return response()->json(['data' => $this->service->batchPayload($batch)], 201);
    }

    public function destroy(Request $request, ExamScanBatch $batch): Response
    {
        $this->authorize('delete', $batch);
        $this->service->deleteBatch($request->user(), $batch);

        return response()->noContent();
    }
}
