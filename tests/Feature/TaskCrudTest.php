<?php

namespace Tests\Feature;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_tasks(): void
    {
        Task::create([
            'title' => 'First Task',
            'description' => 'Description 1',
            'status' => TaskStatus::Pending,
            'priority' => TaskPriority::Low,
        ]);

        Task::create([
            'title' => 'Second Task',
            'description' => 'Description 2',
            'status' => TaskStatus::InProgress,
            'priority' => TaskPriority::High,
        ]);

        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'description', 'status', 'priority', 'created_at', 'updated_at'],
                ],
            ]);
    }

    public function test_can_create_task(): void
    {
        $payload = [
            'title' => 'New Task Title',
            'description' => 'Detailed task description',
            'status' => 'pending',
            'priority' => 'high',
        ];

        $response = $this->postJson('/api/tasks', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'title' => 'New Task Title',
                    'description' => 'Detailed task description',
                    'status' => 'pending',
                    'priority' => 'high',
                ],
            ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'New Task Title',
            'status' => 'pending',
            'priority' => 'high',
        ]);
    }

    public function test_can_create_task_with_default_status_and_priority(): void
    {
        $payload = [
            'title' => 'Task Title Only',
        ];

        $response = $this->postJson('/api/tasks', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'title' => 'Task Title Only',
                    'status' => 'pending',
                    'priority' => 'medium',
                ],
            ]);
    }

    public function test_cannot_create_task_without_title(): void
    {
        $response = $this->postJson('/api/tasks', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_cannot_create_task_with_invalid_status_or_priority(): void
    {
        $response = $this->postJson('/api/tasks', [
            'title' => 'Valid Title',
            'status' => 'invalid_status',
            'priority' => 'invalid_priority',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status', 'priority']);
    }

    public function test_can_show_task(): void
    {
        $task = Task::create([
            'title' => 'Specific Task',
            'description' => 'Some description',
            'status' => TaskStatus::Completed,
            'priority' => TaskPriority::Medium,
        ]);

        $response = $this->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $task->id,
                    'title' => 'Specific Task',
                    'description' => 'Some description',
                    'status' => 'completed',
                    'priority' => 'medium',
                ],
            ]);
    }

    public function test_can_update_task(): void
    {
        $task = Task::create([
            'title' => 'Original Title',
            'status' => TaskStatus::Pending,
            'priority' => TaskPriority::Low,
        ]);

        $payload = [
            'title' => 'Updated Title',
            'status' => 'completed',
            'priority' => 'high',
        ];

        $response = $this->putJson("/api/tasks/{$task->id}", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $task->id,
                    'title' => 'Updated Title',
                    'status' => 'completed',
                    'priority' => 'high',
                ],
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Title',
            'status' => 'completed',
            'priority' => 'high',
        ]);
    }

    public function test_can_delete_task(): void
    {
        $task = Task::create([
            'title' => 'Task To Delete',
        ]);

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id,
        ]);
    }
}

