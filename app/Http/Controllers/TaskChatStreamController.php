<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskChatRequest;
use App\Models\Task;
use App\Services\TaskChatService;
use Laravel\Ai\Responses\StreamableAgentResponse;
use Symfony\Component\HttpFoundation\Response;

class TaskChatStreamController extends Controller
{
    /**
     * Stream an AI chat response for a given task.
     */
    public function __invoke(TaskChatRequest $request, Task $task, TaskChatService $service): Response|StreamableAgentResponse
    {
        return $service->streamChat(
            $task,
            $request->validated('message'),
            $request->validated('conversation_id')
        );
    }
}
