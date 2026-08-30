<?php

namespace App\Services;

use App\Ai\Agents\TaskAnalyzerAgent;
use App\Models\Task;
use App\Models\TaskAnalysis;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TaskAnalysisService
{
    public function __construct(
        protected TaskAnalyzerAgent $agent
    ) {}

    /**
     * Perform AI analysis for a given task, validate structured output, and persist to database.
     */
    public function analyze(Task $task): TaskAnalysis
    {
        $response = $this->agent->analyze($task);

        $data = is_array($response) ? $response : $response->toArray();

        $validated = Validator::make($data, [
            'summary' => ['required', 'string'],
            'complexity' => ['required', 'string', Rule::in(['low', 'medium', 'high'])],
            'estimated_hours' => ['required', 'integer', 'min:1'],
            'steps' => ['required', 'array'],
            'steps.*' => ['required', 'string'],
            'risks' => ['required', 'array'],
            'risks.*' => ['required', 'string'],
        ])->validate();

        return $task->analysis()->updateOrCreate(
            ['task_id' => $task->id],
            $validated
        );
    }
}


