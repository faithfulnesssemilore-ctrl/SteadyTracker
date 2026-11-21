<?php

namespace App\Http\Controllers;

use App\Services\ActivityCsvImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CsvPipelineController extends Controller
{
    public function __construct(
        private readonly ActivityCsvImportService $importService
    ) {}

    // This controller handles the CSV import process, validating the uploaded file and delegating the import logic to the ActivityCsvImportService. It returns a JSON response indicating the success or failure of the import operation.
    public function processCsv(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:102400'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid CSV upload.',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (! $request->hasFile('file')) {
            return response()->json([
                'success' => false,
                'message' => 'No file uploaded.',
            ], 400);
        }

        try {
            $report = $this->importService->import($request->user(), $request->file('file'));

            return response()->json([
                'success' => true,
                'imported' => $report['imported'],
                'rejected' => $report['rejected'],
                'rejections' => $report['rejections'],
            ], 200);
        } catch (\Throwable $exception) {
            $requestId = (string) ($request->headers->get('X-Request-ID') ?: str()->uuid());

            Log::error('CSV import failed', [
                'request_id' => $requestId,
                'user_id' => $request->user()?->id,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'The CSV could not be imported. Please try again later.',
                'request_id' => $requestId,
            ], 422)->header('X-Request-ID', $requestId);
        }
    }
}
