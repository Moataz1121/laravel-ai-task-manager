<?php

namespace App\Ai\Agents;

use App\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\StructuredAgentResponse;

#[Provider(Lab::Gemini)]
#[Model('gemini-3.1-flash-lite')]
class TaskAnalyzerAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): string
    {
        return <<<'INSTRUCTIONS'
You are an expert technical project manager and software architect.
Analyze the provided task details and generate a structured JSON analysis containing:
- summary: A concise summary of the task.
- complexity: Overall complexity level ('low', 'medium', or 'high').
- estimated_hours: Estimated development hours as a positive integer.
- steps: Array of concrete implementation step strings.
- risks: Array of potential risk/technical consideration strings.
INSTRUCTIONS;
    }

    /**
     * Define the structured JSON schema expected from the AI provider.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'summary' => $schema->string()->required(),
            'complexity' => $schema->string()->enum(['low', 'medium', 'high'])->required(),
            'estimated_hours' => $schema->integer()->min(1)->required(),
            'steps' => $schema->array()->items($schema->string())->required(),
            'risks' => $schema->array()->items($schema->string())->required(),
        ];
    }

    /**
     * Send task details to Gemini for structured analysis.
     */
    public function analyze(Task $task): StructuredAgentResponse|AgentResponse
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


