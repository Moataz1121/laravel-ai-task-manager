<?php

namespace App\Ai\Agents;

use App\Ai\Tools\CreateTaskTool;
use App\Ai\Tools\GetTaskTool;
use App\Ai\Tools\ListTasksTool;
use App\Ai\Tools\UpdateTaskTool;
use App\Models\Task;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

#[Provider(Lab::Gemini)]
#[Model('gemini-3.1-flash-lite')]
class TaskChatAgent implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    /**
     * Get the system instructions for the AI chat agent.
     */
    public function instructions(): string
    {
        $instructions = <<<'INSTRUCTIONS'
You are an intelligent AI Task Management Agent capable of inspecting and performing database operations on tasks.

Available Tools:
- list_tasks: Search/filter tasks by status (pending, in_progress, completed) or priority (low, medium, high).
- get_task: Retrieve details for a specific task by its integer ID.
- create_task: Create a new task in the database with title, description, status, and priority.
- update_task: Update an existing task's title, description, status, or priority in the database by its integer ID.

Rules & Responsibilities:
1. Automatically decide when to call the appropriate tool to execute user requests.
2. Never claim that a task was created, updated, or retrieved unless the tool actually executed successfully and returned positive output.
3. Formulate your final response by summarizing the output returned by the tool.
4. If a tool fails or returns an error, respect the tool output and inform the user clearly about the failure.
INSTRUCTIONS;

        $task = $this->conversationUser;

        if ($task instanceof Task) {
            $description = $task->description ?? 'No description provided.';
            $status = $task->status->value;
            $priority = $task->priority->value;

            $instructions .= <<<SCOPED_CONTEXT

TASK CONTEXT & SECURITY SCOPING:
- Current Scoped Task ID: {$task->id}
- Title: {$task->title}
- Description: {$description}
- Status: {$status}
- Priority: {$priority}

Scoping Rules:
1. This conversation is strictly scoped to Task #{$task->id}.
2. When the user asks to modify, update, or change the status of "this task", perform the action on Task #{$task->id}.
3. You MUST NOT attempt to modify an unrelated task (a task ID other than #{$task->id}) from within this conversation. The update_task tool enforces this boundary and will reject attempts to modify unrelated tasks.
4. Creating a new task (create_task) or querying project tasks (list_tasks, get_task) remains permitted.
SCOPED_CONTEXT;
        }

        return $instructions;
    }

    /**
     * Get the tools available to the agent.
     */
    public function tools(): array
    {
        $task = $this->conversationUser instanceof Task ? $this->conversationUser : null;

        return [
            new ListTasksTool,
            new GetTaskTool,
            new CreateTaskTool,
            new UpdateTaskTool($task),
        ];
    }
}
