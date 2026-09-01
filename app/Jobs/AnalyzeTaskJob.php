<?php

namespace App\Jobs;

use App\Models\Task;
use App\Services\TaskAnalysisService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnalyzeTaskJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Task $task,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(TaskAnalysisService $service): void
    {
        $service->analyzeTask($this->task);
    }
}

