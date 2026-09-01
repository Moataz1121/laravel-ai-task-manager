<?php

namespace Tests\Feature;

use App\Ai\Agents\TaskAnalyzerAgent;
use App\Ai\Agents\TaskChatAgent;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Jobs\AnalyzeTaskJob;
use App\Models\Task;
use App\Services\TaskAnalysisService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Responses\Data\ToolCall;
use Tests\TestCase;

class QueuedAiTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_agent_request_can_be_queued(): void
    {
        TaskChatAgent::fake();

        $response = $this->postJson('/api/agent/chat/queue', [
            'message' => 'Analyze my pending tasks and suggest what I should work on next.',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'queued',
                'message' => 'AI request has been queued.',
            ]);

        TaskChatAgent::assertQueued(function ($prompt) {
            return $prompt->contains('Analyze my pending tasks');
        });
    }

    public function test_queued_agent_uses_existing_tool_architecture(): void
    {
        TaskChatAgent::fake([
            new ToolCall('call_q1', 'create_task', [
                'title' => 'Queued Task Creation',
                'priority' => 'high',
            ]),
            'Created task in background worker.',
        ]);

        $agent = TaskChatAgent::make();
        $agent->queue('Create a high priority task');

        $this->assertDatabaseHas('tasks', [
            'title' => 'Queued Task Creation',
        ]);
    }

    public function test_task_analysis_can_be_queued(): void
    {
        Queue::fake();

        $task = Task::create(['title' => 'Analyze me queued', 'status' => TaskStatus::Pending, 'priority' => TaskPriority::Medium]);

        $response = $this->postJson("/api/tasks/{$task->id}/analyze/queue");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'queued',
                'message' => 'Task analysis has been queued.',
            ]);

        Queue::assertPushed(AnalyzeTaskJob::class, function ($job) use ($task) {
            return $job->task->id === $task->id;
        });
    }

    public function test_queued_task_analysis_job_persists_structured_result(): void
    {
        TaskAnalyzerAgent::fake([
            [
                'summary' => 'Queued analysis summary',
                'complexity' => 'medium',
                'estimated_hours' => 8,
                'steps' => ['Step A', 'Step B'],
                'risks' => ['Risk X'],
            ],
        ]);

        $task = Task::create(['title' => 'Job Execution Task', 'status' => TaskStatus::Pending, 'priority' => TaskPriority::Medium]);

        $job = new AnalyzeTaskJob($task);
        $job->handle(app(TaskAnalysisService::class));

        $this->assertDatabaseHas('task_analyses', [
            'task_id' => $task->id,
            'summary' => 'Queued analysis summary',
            'complexity' => 'medium',
            'estimated_hours' => 8,
        ]);
    }

    public function test_queued_task_analysis_job_failure_handling(): void
    {
        $task = Task::create(['title' => 'Failing Analysis Task', 'status' => TaskStatus::Pending, 'priority' => TaskPriority::Medium]);

        $serviceMock = $this->createMock(TaskAnalysisService::class);
        $serviceMock->expects($this->once())
            ->method('analyzeTask')
            ->willThrowException(new Exception('AI Provider Error'));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('AI Provider Error');

        $job = new AnalyzeTaskJob($task);
        $job->handle($serviceMock);
    }
}

