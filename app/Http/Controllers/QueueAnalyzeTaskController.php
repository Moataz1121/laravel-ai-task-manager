<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Services\TaskAnalysisService;
use Illuminate\Http\JsonResponse;

class QueueAnalyzeTaskController extends Controller
{
    /**
     * Queue AI analysis for the given task.
     */
    public function __invoke(Task $task, TaskAnalysisService $service): JsonResponse
    {
        $service->queueAnalysis($task);

        return response()->json([
            'status' => 'queued',
            'message' => 'Task analysis has been queued.',
        ]);
    }
}

