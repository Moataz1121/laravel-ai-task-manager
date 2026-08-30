<?php

namespace Tests\Feature;

use App\Ai\Agents\TaskAnalyzerAgent;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyzeTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_analyze_task_and_persist_structured_analysis(): void
    {
        TaskAnalyzerAgent::fake([
            [
                'summary' => 'Refactor authentication module to modern OAuth2.',
                'complexity' => 'medium',
                'estimated_hours' => 12,
                'steps' => ['Install Socialite', 'Configure credentials', 'Add callbacks'],
                'risks' => ['Token expiration edge cases'],
            ],
        ]);

        $task = Task::create([
            'title' => 'Refactor Auth Module',
            'description' => 'Upgrade authentication to modern OAuth2 standard',
            'status' => TaskStatus::InProgress,
            'priority' => TaskPriority::High,
        ]);

        $response = $this->postJson("/api/tasks/{$task->id}/analyze");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'task_id' => $task->id,
                    'summary' => 'Refactor authentication module to modern OAuth2.',
                    'complexity' => 'medium',
                    'estimated_hours' => 12,
                    'steps' => ['Install Socialite', 'Configure credentials', 'Add callbacks'],
                    'risks' => ['Token expiration edge cases'],
                ],
            ]);

        $this->assertDatabaseHas('task_analyses', [
            'task_id' => $task->id,
            'summary' => 'Refactor authentication module to modern OAuth2.',
            'complexity' => 'medium',
            'estimated_hours' => 12,
        ]);

        TaskAnalyzerAgent::assertPromptedTimes(1);
    }

    public function test_reanalyzing_task_updates_existing_analysis_without_duplicates(): void
    {
        TaskAnalyzerAgent::fake([
            [
                'summary' => 'Initial analysis',
                'complexity' => 'low',
                'estimated_hours' => 4,
                'steps' => ['Step 1'],
                'risks' => ['Risk 1'],
            ],
            [
                'summary' => 'Updated analysis',
                'complexity' => 'high',
                'estimated_hours' => 16,
                'steps' => ['Step 1', 'Step 2'],
                'risks' => ['Risk 1', 'Risk 2'],
            ],
        ]);

        $task = Task::create([
            'title' => 'Task Title',
            'status' => TaskStatus::Pending,
            'priority' => TaskPriority::Low,
        ]);

        $this->postJson("/api/tasks/{$task->id}/analyze");
        $this->assertDatabaseCount('task_analyses', 1);

        $this->postJson("/api/tasks/{$task->id}/analyze");
        $this->assertDatabaseCount('task_analyses', 1);

        $this->assertDatabaseHas('task_analyses', [
            'task_id' => $task->id,
            'summary' => 'Updated analysis',
            'complexity' => 'high',
            'estimated_hours' => 16,
        ]);
    }

    public function test_returns_404_when_analyzing_non_existent_task(): void
    {
        $response = $this->postJson('/api/tasks/999/analyze');

        $response->assertStatus(404);
    }
}


