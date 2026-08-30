<?php

namespace App\Http\Controllers;

use App\Http\Resources\TaskAnalysisResource;
use App\Models\Task;
use App\Services\TaskAnalysisService;
use Illuminate\Http\JsonResponse;
use Throwable;

class AnalyzeTaskController extends Controller
{
    /**
     * Analyze a task using Gemini AI and return the structured saved analysis.
     */
    public function __invoke(Task $task, TaskAnalysisService $service): JsonResponse
    {
        try {
            $analysis = $service->analyze($task);

            return response()->json([
                'status' => 'success',
                'data' => new TaskAnalysisResource($analysis),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to analyze task: '.$e->getMessage(),
            ], 500);
        }
    }
}


