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

    public function test_can_analyze_task_using_ai(): void
    {
        TaskAnalyzerAgent::fake([
            "1. Summary: Refactor authentication module.\n2. Recommended Approach: Use OAuth2.\n3. Main Implementation Steps: Step 1, Step 2.\n4. Complexity: Medium.\n5. Risks: Token expiration edge cases.",
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
                'task_id' => $task->id,
                'analysis' => "1. Summary: Refactor authentication module.\n2. Recommended Approach: Use OAuth2.\n3. Main Implementation Steps: Step 1, Step 2.\n4. Complexity: Medium.\n5. Risks: Token expiration edge cases.",
            ]);

        TaskAnalyzerAgent::assertPromptedTimes(1);
    }

    public function test_returns_404_when_analyzing_non_existent_task(): void
    {
        $response = $this->postJson('/api/tasks/999/analyze');

        $response->assertStatus(404);
    }
}

