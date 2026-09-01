<?php

namespace App\Http\Controllers;

use App\Http\Requests\GlobalAgentChatRequest;
use App\Services\TaskChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Throwable;

class GlobalAgentChatController extends Controller
{
    /**
     * Send a message to the global AI agent chat.
     */
    public function __invoke(GlobalAgentChatRequest $request, TaskChatService $service): JsonResponse
    {
        try {
            $response = $service->globalChat(
                $request->validated('message'),
                $request->validated('conversation_id')
            );

            return response()->json([
                'status' => 'success',
                'conversation_id' => $response->conversationId,
                'message' => $response->text,
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process AI agent message: '.$e->getMessage(),
            ], 500);
        }
    }
}

