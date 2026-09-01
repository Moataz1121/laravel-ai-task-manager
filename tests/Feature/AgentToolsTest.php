<?php

namespace Tests\Feature;

use App\Ai\Agents\TaskChatAgent;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Responses\Data\ToolCall;
use Tests\TestCase;

class AgentToolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_invokes_create_task_tool_when_asked(): void
    {
        TaskChatAgent::fake([
            new ToolCall('call_1', 'create_task', [
                'title' => 'Implement Stripe Payments',
                'priority' => 'high',
            ]),
            'I have created the high priority task "Implement Stripe Payments".',
        ]);

        $task = Task::create(['title' => 'Initial Task']);

        $response = $this->postJson("/api/tasks/{$task->id}/chat", [
            'message' => 'Create a high priority task to implement Stripe payments.',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'I have created the high priority task "Implement Stripe Payments".',
            ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Implement Stripe Payments',
            'priority' => TaskPriority::High->value,
        ]);
    }

    public function test_agent_invokes_list_tasks_tool_when_asked(): void
    {
        Task::create(['title' => 'High Task 1', 'priority' => TaskPriority::High]);
        Task::create(['title' => 'High Task 2', 'priority' => TaskPriority::High]);

        TaskChatAgent::fake([
            new ToolCall('call_2', 'list_tasks', ['priority' => 'high']),
            'Here are your high priority tasks: High Task 1, High Task 2.',
        ]);

        $task = Task::create(['title' => 'Context Task']);

        $response = $this->postJson("/api/tasks/{$task->id}/chat", [
            'message' => 'Show me my high priority tasks.',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Here are your high priority tasks: High Task 1, High Task 2.',
            ]);
    }

    public function test_can_update_scoped_task_in_task_scoped_conversation(): void
    {
        $task = Task::create(['title' => 'Task 1', 'status' => TaskStatus::Pending]);

        TaskChatAgent::fake([
            new ToolCall('call_valid', 'update_task', [
                'id' => $task->id,
                'status' => 'completed',
            ]),
            "Task {$task->id} status has been updated to completed.",
        ]);

        $response = $this->postJson("/api/tasks/{$task->id}/chat", [
            'message' => "Change task {$task->id} status to completed.",
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => "Task {$task->id} status has been updated to completed.",
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => TaskStatus::Completed->value,
        ]);
    }

    public function test_cannot_modify_unrelated_task_in_task_scoped_conversation(): void
    {
        $scopedTask = Task::create(['title' => 'Scoped Task 1', 'status' => TaskStatus::Pending]);
        $unrelatedTask = Task::create(['title' => 'Unrelated Task 5', 'status' => TaskStatus::Pending]);

        TaskChatAgent::fake([
            new ToolCall('call_sec', 'update_task', [
                'id' => $unrelatedTask->id,
                'status' => 'completed',
            ]),
            "Cannot modify unrelated Task #{$unrelatedTask->id} from a conversation scoped to Task #{$scopedTask->id}.",
        ]);

        $response = $this->postJson("/api/tasks/{$scopedTask->id}/chat", [
            'message' => "Change task {$unrelatedTask->id} status to completed.",
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => "Cannot modify unrelated Task #{$unrelatedTask->id} from a conversation scoped to Task #{$scopedTask->id}.",
            ]);

        // Verify Task #5 remains UNCHANGED in the database!
        $this->assertDatabaseHas('tasks', [
            'id' => $unrelatedTask->id,
            'status' => TaskStatus::Pending->value,
        ]);
    }
}

