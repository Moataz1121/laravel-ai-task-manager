<?php

namespace Tests\Feature;

use App\Ai\Agents\TaskChatAgent;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Responses\Data\ToolCall;
use Tests\TestCase;

class GlobalAgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_agent_can_create_task(): void
    {
        TaskChatAgent::fake([
            new ToolCall('call_g1', 'create_task', [
                'title' => 'Stripe Payments Integration',
                'priority' => 'high',
            ]),
            'Created high priority task "Stripe Payments Integration".',
        ]);

        $response = $this->postJson('/api/agent/chat', [
            'message' => 'Create a high priority task to implement Stripe payments.',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Created high priority task "Stripe Payments Integration".',
            ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Stripe Payments Integration',
            'priority' => TaskPriority::High->value,
        ]);
    }

    public function test_global_agent_can_list_tasks(): void
    {
        Task::create(['title' => 'Pending Task 1', 'status' => TaskStatus::Pending]);
        Task::create(['title' => 'Pending Task 2', 'status' => TaskStatus::Pending]);

        TaskChatAgent::fake([
            new ToolCall('call_g2', 'list_tasks', ['status' => 'pending']),
            'Here are your pending tasks: Pending Task 1, Pending Task 2.',
        ]);

        $response = $this->postJson('/api/agent/chat', [
            'message' => 'Show me all pending tasks.',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Here are your pending tasks: Pending Task 1, Pending Task 2.',
            ]);
    }

    public function test_global_agent_can_get_task_details(): void
    {
        $task = Task::create(['title' => 'Inspectable Task']);

        TaskChatAgent::fake([
            new ToolCall('call_g3', 'get_task', ['id' => $task->id]),
            "Details for task {$task->id}: Inspectable Task.",
        ]);

        $response = $this->postJson('/api/agent/chat', [
            'message' => "Get details for task {$task->id}.",
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => "Details for task {$task->id}: Inspectable Task.",
            ]);
    }

    public function test_global_agent_can_update_any_task(): void
    {
        $task = Task::create(['title' => 'Task 5', 'status' => TaskStatus::Pending]);

        TaskChatAgent::fake([
            new ToolCall('call_g4', 'update_task', [
                'id' => $task->id,
                'status' => 'completed',
            ]),
            "Task {$task->id} updated to completed.",
        ]);

        $response = $this->postJson('/api/agent/chat', [
            'message' => "Change task {$task->id} status to completed.",
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => "Task {$task->id} updated to completed.",
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => TaskStatus::Completed->value,
        ]);
    }

    public function test_global_agent_supports_conversation_continuation(): void
    {
        TaskChatAgent::fake([
            'Hello! How can I help you manage your tasks today?',
        ]);

        $firstResponse = $this->postJson('/api/agent/chat', [
            'message' => 'Hello AI Agent',
        ]);

        $firstResponse->assertStatus(200);
        $conversationId = $firstResponse->json('conversation_id');
        $this->assertNotEmpty($conversationId);

        TaskChatAgent::fake([
            'I remember our conversation!',
        ]);

        $secondResponse = $this->postJson('/api/agent/chat', [
            'conversation_id' => $conversationId,
            'message' => 'Do you remember me?',
        ]);

        $secondResponse->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'conversation_id' => $conversationId,
                'message' => 'I remember our conversation!',
            ]);
    }

    public function test_global_agent_supports_streaming(): void
    {
        TaskChatAgent::fake([
            'Streaming global AI agent response.',
        ]);

        $response = $this->postJson('/api/agent/chat/stream', [
            'message' => 'Stream all my tasks',
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('text/event-stream', (string) $response->headers->get('Content-Type'));
    }
}

