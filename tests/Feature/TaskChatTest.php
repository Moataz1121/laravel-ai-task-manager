<?php

namespace Tests\Feature;

use App\Ai\Agents\TaskChatAgent;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_start_and_continue_chat_conversation_for_task(): void
    {
        TaskChatAgent::fake(function (string $prompt) {
            if (str_contains(strtolower($prompt), 'security')) {
                return 'Regarding security, implement strict input validation and token handling.';
            }

            return 'To start this task, begin by setting up your project environment.';
        });

        $task = Task::create([
            'title' => 'Build OAuth2 Integration',
            'description' => 'Add Google and GitHub OAuth logins',
            'status' => TaskStatus::InProgress,
            'priority' => TaskPriority::High,
        ]);

        // First message
        $firstResponse = $this->postJson("/api/tasks/{$task->id}/chat", [
            'message' => 'How should I start this task?',
        ]);

        $firstResponse->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'task_id' => $task->id,
                'message' => 'To start this task, begin by setting up your project environment.',
            ]);

        $conversationId = $firstResponse->json('conversation_id');
        $this->assertNotEmpty($conversationId);

        $this->assertDatabaseHas('agent_conversations', [
            'id' => $conversationId,
            'participant_type' => Task::class,
            'participant_id' => $task->id,
        ]);

        // Second message
        $secondResponse = $this->postJson("/api/tasks/{$task->id}/chat", [
            'message' => 'What about security?',
        ]);

        $secondResponse->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'task_id' => $task->id,
                'conversation_id' => $conversationId,
                'message' => 'Regarding security, implement strict input validation and token handling.',
            ]);

        TaskChatAgent::assertPromptedTimes(2);

        $this->assertDatabaseHas('agent_conversation_messages', [
            'conversation_id' => $conversationId,
            'role' => 'user',
            'content' => 'How should I start this task?',
        ]);

        $this->assertDatabaseHas('agent_conversation_messages', [
            'conversation_id' => $conversationId,
            'role' => 'user',
            'content' => 'What about security?',
        ]);
    }

    public function test_cannot_send_empty_chat_message(): void
    {
        $task = Task::create([
            'title' => 'Sample Task',
        ]);

        $response = $this->postJson("/api/tasks/{$task->id}/chat", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }
}

