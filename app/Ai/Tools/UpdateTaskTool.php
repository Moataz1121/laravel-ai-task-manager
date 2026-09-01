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

class UpdateTaskTool implements Tool
{
    /**
     * Create a new UpdateTaskTool instance.
     */
    public function __construct(
        protected ?Task $scopedTask = null,
    ) {}

    /**
     * Get the name of the tool.
     */
    public function name(): string
    {
        return 'update_task';
    }

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Update an existing task in the database by its ID.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $validator = Validator::make($request->all(), [
            'id' => ['required', 'integer', 'min:1'],
            'title' => ['nullable', 'string', 'max:255'],
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

        $targetId = (int) $request['id'];

        // Enforce task scoping security boundary if running inside a task-scoped conversation
        if ($this->scopedTask !== null && $this->scopedTask->id !== $targetId) {
            return json_encode([
                'status' => 'error',
                'message' => "Security Error: Cannot modify unrelated Task #{$targetId} from a conversation scoped to Task #{$this->scopedTask->id}.",
            ]);
        }

        $task = Task::find($targetId);

        if (! $task) {
            return json_encode([
                'status' => 'error',
                'message' => 'Task not found.',
            ]);
        }

        $data = array_filter([
            'title' => $request['title'] ?? null,
            'description' => $request['description'] ?? null,
            'status' => isset($request['status']) ? TaskStatus::tryFrom($request['status']) : null,
            'priority' => isset($request['priority']) ? TaskPriority::tryFrom($request['priority']) : null,
        ], fn ($val) => $val !== null);

        $task->update($data);

        return json_encode([
            'status' => 'success',
            'message' => 'Task updated successfully.',
            'task' => $task->fresh()->toArray(),
        ]);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->min(1)->required(),
            'title' => $schema->string(),
            'description' => $schema->string(),
            'status' => $schema->string()->enum(['pending', 'in_progress', 'completed']),
            'priority' => $schema->string()->enum(['low', 'medium', 'high']),
        ];
    }
}

