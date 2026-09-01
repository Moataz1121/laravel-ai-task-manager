<?php

namespace App\Ai\Tools;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Validator;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ListTasksTool implements Tool
{
    /**
     * Get the name of the tool.
     */
    public function name(): string
    {
        return 'list_tasks';
    }

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'List tasks from the database with optional filtering by status (pending, in_progress, completed) or priority (low, medium, high).';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $validator = Validator::make($request->all(), [
            'status' => ['nullable', 'string'],
            'priority' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return json_encode([
                'status' => 'error',
                'errors' => $validator->errors()->all(),
            ]);
        }

        $query = Task::query();

        if (isset($request['status']) && filled($request['status'])) {
            $status = TaskStatus::tryFrom($request['status']);
            if ($status) {
                $query->where('status', $status);
            }
        }

        if (isset($request['priority']) && filled($request['priority'])) {
            $priority = TaskPriority::tryFrom($request['priority']);
            if ($priority) {
                $query->where('priority', $priority);
            }
        }

        $tasks = $query->latest('id')->get();

        return json_encode([
            'status' => 'success',
            'count' => $tasks->count(),
            'tasks' => $tasks->toArray(),
        ]);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()->enum(['pending', 'in_progress', 'completed']),
            'priority' => $schema->string()->enum(['low', 'medium', 'high']),
        ];
    }
}

