<?php

namespace App\Ai\Tools;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CreateTaskTool implements Tool
{
    /**
     * Get the name of the tool.
     */
    public function name(): string
    {
        return 'create_task';
    }

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Create a new task in the database with a title, optional description, status, and priority.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', Rule::enum(TaskStatus::class)],
            'priority' => ['nullable', 'string', Rule::enum(TaskPriority::class)],
        ]);

        if ($validator->fails()) {
            return json_encode([
                'status' => 'error',
                'errors' => $validator->errors()->all(),
            ]);
        }

        $data = $validator->validated();

        if (isset($data['status'])) {
            $data['status'] = TaskStatus::from($data['status']);
        }

        if (isset($data['priority'])) {
            $data['priority'] = TaskPriority::from($data['priority']);
        }

        $task = Task::create($data);

        return json_encode([
            'status' => 'success',
            'message' => 'Task created successfully.',
            'task' => $task->toArray(),
        ]);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->required(),
            'description' => $schema->string(),
            'status' => $schema->string()->enum(['pending', 'in_progress', 'completed']),
            'priority' => $schema->string()->enum(['low', 'medium', 'high']),
        ];
    }
}

