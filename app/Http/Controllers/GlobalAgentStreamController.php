<?php

namespace App\Http\Controllers;

use App\Http\Requests\GlobalAgentChatRequest;
use App\Services\TaskChatService;
use Laravel\Ai\Responses\StreamableAgentResponse;
use Symfony\Component\HttpFoundation\Response;

class GlobalAgentStreamController extends Controller
{
    /**
     * Stream a response from the global AI agent chat.
     */
    public function __invoke(GlobalAgentChatRequest $request, TaskChatService $service): Response|StreamableAgentResponse
    {
        return $service->globalStreamChat(
            $request->validated('message'),
            $request->validated('conversation_id')
        );
    }
}

