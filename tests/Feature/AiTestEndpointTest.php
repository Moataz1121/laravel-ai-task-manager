<?php

namespace Tests\Feature;

use App\Ai\Agents\AiTestAgent;
use Tests\TestCase;

class AiTestEndpointTest extends TestCase
{
    public function test_ai_test_endpoint_returns_successful_json_response_with_agent_fake(): void
    {
        AiTestAgent::fake(['Hello from fake Gemini!']);

        $response = $this->getJson('/api/ai-test');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'response' => 'Hello from fake Gemini!',
            ]);

        AiTestAgent::assertPromptedTimes(1);
    }
}

