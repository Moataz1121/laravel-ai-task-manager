<?php

namespace App\Http\Controllers;

use App\Ai\Agents\AiTestAgent;
use Illuminate\Http\JsonResponse;

class AiTestController extends Controller
{
    /**
     * Send a test prompt to Gemini and return the AI response as JSON.
     */
    public function __invoke(): JsonResponse
    {
        $response = (new AiTestAgent)->prompt('Hello Gemini! Respond with a short confirmation message that you are online and working.');

        return response()->json([
            'status' => 'success',
            'response' => $response->text,
        ]);
    }
}

