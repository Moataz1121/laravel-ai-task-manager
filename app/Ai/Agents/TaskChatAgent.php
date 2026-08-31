<?php

namespace App\Ai\Agents;

use App\Models\Task;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

#[Provider(Lab::Gemini)]
#[Model('gemini-3.1-flash-lite')]
class TaskChatAgent implements Agent, Conversational
{
    use Promptable, RemembersConversations;

    /**
     * Get the system instructions for the AI chat agent.
     */
    public function instructions(): string
    {
        $task = $this->conversationUser;

        if ($task instanceof Task) {
            $description = $task->description ?? 'No description provided.';
            $status = $task->status->value;
            $priority = $task->priority->value;

            return <<<INSTRUCTIONS
You are an intelligent AI assistant helping a developer with a specific task.

Task Details:
- Title: {$task->title}
- Description: {$description}
- Status: {$status}
- Priority: {$priority}

Answer the user's questions specifically in the context of this task. Maintain context from previous messages in the conversation and assist them effectively.
INSTRUCTIONS;
        }

        return 'You are an intelligent AI assistant helping with a task.';
    }
}

