<?php

namespace App\Http\Controllers;

use App\Http\Requests\GlobalAgentChatRequest;
use App\Services\TaskChatService;
use Illuminate\Http\JsonResponse;

class QueueGlobalAgentChatController extends Controller
{
    /**
     * Queue a global AI agent chat request.
     */
    public function __invoke(GlobalAgentChatRequest $request, TaskChatService $service): JsonResponse
    {
        $service->queueGlobalChat(
            $request->validated('message'),
            $request->validated('conversation_id')
        );

        return response()->json([
            'status' => 'queued',
            'message' => 'AI request has been queued.',
        ]);
    }
}

