<?php

namespace App\Ai\Tools;

use App\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Validator;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetTaskTool implements Tool
{
    /**
     * Get the name of the tool.
     */
    public function name(): string
    {
        return 'get_task';
    }

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Get details of a specific task by its integer ID.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $validator = Validator::make($request->all(), [
            'id' => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return json_encode([
                'status' => 'error',
                'errors' => $validator->errors()->all(),
            ]);
        }

        $task = Task::with('analysis')->find($request['id']);

        if (! $task) {
            return json_encode([
                'status' => 'error',
                'message' => 'Task not found.',
            ]);
        }

        return json_encode([
            'status' => 'success',
            'task' => $task->toArray(),
        ]);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->min(1)->required(),
        ];
    }
}

