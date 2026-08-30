<?php

namespace App\Ai\Agents;

use App\Models\Task;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Laravel\Ai\Responses\AgentResponse;

#[Provider(Lab::Gemini)]
#[Model('gemini-3.1-flash-lite')]
class TaskAnalyzerAgent implements Agent
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): string
    {
        return <<<'INSTRUCTIONS'
You are an expert technical project manager and software architect.
When presented with a task, analyze it carefully and provide a comprehensive response containing:
1. A short summary of the task.
2. The recommended approach to solve it.
3. The main implementation steps required.
4. An estimated complexity level (e.g., Low, Medium, High) with rationale.
5. Potential risks or technical considerations to keep in mind.
INSTRUCTIONS;
    }

    /**
     * Send task details to Gemini for analysis.
     */
    public function analyze(Task $task): AgentResponse
    {
        $description = $task->description ?? 'No description provided.';
        $status = $task->status->value;
        $priority = $task->priority->value;

        $prompt = <<<PROMPT
Please analyze the following task:
- Title: {$task->title}
- Description: {$description}
- Status: {$status}
- Priority: {$priority}
PROMPT;

        return $this->prompt($prompt);
    }
}

