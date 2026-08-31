<?php

namespace App\Http\Controllers;

use App\Http\Resources\TaskConversationResource;
use App\Models\Task;
use App\Services\TaskChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class TaskConversationController extends Controller
{
    /**
     * List all conversations belonging to the specified task.
     */
    public function index(Task $task, TaskChatService $service): JsonResponse
    {
        try {
            $conversations = $service->listConversations($task);

            return response()->json([
                'status' => 'success',
                'task_id' => $task->id,
                'data' => TaskConversationResource::collection($conversations),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve task conversations: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Explicitly create a new conversation for the specified task.
     */
    public function store(Request $request, Task $task, TaskChatService $service): JsonResponse
    {
        try {
            $request->validate([
                'title' => ['nullable', 'string', 'max:255'],
            ]);

            $conversation = $service->createConversation($task, $request->input('title'));

            return response()->json([
                'status' => 'success',
                'task_id' => $task->id,
                'data' => new TaskConversationResource($conversation),
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create conversation: '.$e->getMessage(),
            ], 500);
        }
    }
}
