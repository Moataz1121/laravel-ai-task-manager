<?php

namespace Tests\Feature;

use App\Ai\Agents\TaskChatAgent;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskChatStreamTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_stream_ai_chat_response_for_task(): void
    {
        TaskChatAgent::fake([
            'To implement this task, start by defining the controller and service.',
        ]);

        $task = Task::create([
            'title' => 'Streaming Task Feature',
            'status' => TaskStatus::InProgress,
            'priority' => TaskPriority::High,
        ]);

        $response = $this->postJson("/api/tasks/{$task->id}/chat/stream", [
            'message' => 'Explain how I should implement this task',
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('text/event-stream', (string) $response->headers->get('Content-Type'));

        TaskChatAgent::assertPromptedTimes(1);
    }

    public function test_stream_requires_valid_message(): void
    {
        $task = Task::create([
            'title' => 'Sample Task',
        ]);

        $response = $this->postJson("/api/tasks/{$task->id}/chat/stream", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }
}
