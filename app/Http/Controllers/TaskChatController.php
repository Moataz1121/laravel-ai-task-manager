<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskChatRequest;
use App\Models\Task;
use App\Services\TaskChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Throwable;

class TaskChatController extends Controller
{
    /**
     * Send a message to the task AI chat and return the latest AI response.
     */
    public function __invoke(TaskChatRequest $request, Task $task, TaskChatService $service): JsonResponse
    {
        try {
            $response = $service->chat(
                $task,
                $request->validated('message'),
                $request->validated('conversation_id')
            );

            return response()->json([
                'status' => 'success',
                'task_id' => $task->id,
                'conversation_id' => $response->conversationId,
                'message' => $response->text,
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process AI chat message: '.$e->getMessage(),
            ], 500);
        }
    }
}
