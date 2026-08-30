<?php

namespace App\Services;

use App\Ai\Agents\TaskAnalyzerAgent;
use App\Models\Task;

class TaskAnalysisService
{
    public function __construct(
        protected TaskAnalyzerAgent $agent
    ) {}

    /**
     * Perform AI analysis for a given task.
     */
    public function analyze(Task $task): string
    {
        $response = $this->agent->analyze($task);

        return $response->text;
    }
}

