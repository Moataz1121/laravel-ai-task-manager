<?php

namespace Tests\Unit;

use App\Ai\Tools\CreateTaskTool;
use App\Ai\Tools\GetTaskTool;
use App\Ai\Tools\ListTasksTool;
use App\Ai\Tools\UpdateTaskTool;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class ToolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_task_tool_creates_task_and_returns_json(): void
    {
        $tool = new CreateTaskTool;
        $request = new Request([
            'title' => 'Implement Stripe Integration',
            'description' => 'Setup webhooks and subscriptions',
            'priority' => 'high',
            'status' => 'pending',
        ]);

        $result = json_decode($tool->handle($request), true);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals('Implement Stripe Integration', $result['task']['title']);
        $this->assertDatabaseHas('tasks', [
            'title' => 'Implement Stripe Integration',
            'priority' => TaskPriority::High->value,
        ]);
    }

    public function test_list_tasks_tool_filters_by_priority_and_status(): void
    {
        Task::create(['title' => 'High Priority Task', 'priority' => TaskPriority::High, 'status' => TaskStatus::Pending]);
        Task::create(['title' => 'Low Priority Task', 'priority' => TaskPriority::Low, 'status' => TaskStatus::Pending]);

        $tool = new ListTasksTool;
        $request = new Request(['priority' => 'high']);

        $result = json_decode($tool->handle($request), true);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals(1, $result['count']);
        $this->assertEquals('High Priority Task', $result['tasks'][0]['title']);
    }

    public function test_get_task_tool_returns_task_details_or_error_for_non_existent(): void
    {
        $task = Task::create(['title' => 'Existing Task']);

        $tool = new GetTaskTool;

        // Existing
        $found = json_decode($tool->handle(new Request(['id' => $task->id])), true);
        $this->assertEquals('success', $found['status']);
        $this->assertEquals('Existing Task', $found['task']['title']);

        // Non-existent
        $notFound = json_decode($tool->handle(new Request(['id' => 9999])), true);
        $this->assertEquals('error', $notFound['status']);
        $this->assertEquals('Task not found.', $notFound['message']);
    }

    public function test_update_task_tool_updates_existing_task(): void
    {
        $task = Task::create(['title' => 'Old Title', 'status' => TaskStatus::Pending]);

        $tool = new UpdateTaskTool($task);
        $request = new Request([
            'id' => $task->id,
            'title' => 'Updated Title',
            'status' => 'completed',
        ]);

        $result = json_decode($tool->handle($request), true);

        $this->assertEquals('success', $result['status']);
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Title',
            'status' => TaskStatus::Completed->value,
        ]);
    }

    public function test_update_task_tool_rejects_unrelated_task_modification(): void
    {
        $task1 = Task::create(['title' => 'Scoped Task 1', 'status' => TaskStatus::Pending]);
        $task5 = Task::create(['title' => 'Unrelated Task 5', 'status' => TaskStatus::Pending]);

        $tool = new UpdateTaskTool($task1);
        $request = new Request([
            'id' => $task5->id,
            'status' => 'completed',
        ]);

        $result = json_decode($tool->handle($request), true);

        $this->assertEquals('error', $result['status']);
        $this->assertStringContainsString('Security Error: Cannot modify unrelated Task', $result['message']);
        $this->assertDatabaseHas('tasks', [
            'id' => $task5->id,
            'status' => TaskStatus::Pending->value,
        ]);
    }

    public function test_tools_reject_invalid_arguments_safely(): void
    {
        $tool = new CreateTaskTool;
        // Missing title
        $result = json_decode($tool->handle(new Request([])), true);

        $this->assertEquals('error', $result['status']);
        $this->assertNotEmpty($result['errors']);
    }
}

