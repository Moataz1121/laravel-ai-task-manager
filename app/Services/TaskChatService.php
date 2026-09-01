<?php

namespace App\Services;

use App\Ai\Agents\TaskChatAgent;
use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Ai\Models\Conversation;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\QueuedAgentResponse;

class TaskChatService
{
    public function __construct(
        protected ConversationStore $conversationStore,
    ) {}

    /**
     * Get all conversations associated with the given task.
     *
     * @return Collection<int, Conversation>
     */
    public function listConversations(Task $task): Collection
    {
        return $task->conversations()->latest('updated_at')->get();
    }

    /**
     * Explicitly create a new conversation for the given task.
     */
    public function createConversation(Task $task, ?string $title = null): Conversation
    {
        $title = $title ?: 'Task Conversation: '.$task->title;

        $conversationId = $this->conversationStore->storeConversation(
            Conversation::participantType($task),
            Conversation::participantKey($task),
            $title
        );

        return Conversation::findOrFail($conversationId);
    }

    /**
     * Send a message to a task conversation.
     * If conversation_id is provided, verify it belongs to the task and continue it.
     * If omitted, explicitly start a brand new conversation for the task.
     */
    public function chat(Task $task, string $message, ?string $conversationId = null): AgentResponse
    {
        $agent = TaskChatAgent::make();

        if ($conversationId !== null) {
            $this->verifyConversationBelongsToTask($task, $conversationId);

            return $agent
                ->continue($conversationId, as: $task)
                ->prompt($message);
        }

        return $agent
            ->forParticipant($task)
            ->prompt($message);
    }

    /**
     * Stream an AI chat response for a task conversation.
     * If conversation_id is provided, verify it belongs to the task and continue it.
     * If omitted, explicitly start a brand new conversation for the task.
     */
    public function streamChat(Task $task, string $message, ?string $conversationId = null): \Laravel\Ai\Responses\StreamableAgentResponse
    {
        $agent = TaskChatAgent::make();

        if ($conversationId !== null) {
            $this->verifyConversationBelongsToTask($task, $conversationId);

            return $agent
                ->continue($conversationId, as: $task)
                ->stream($message);
        }

        return $agent
            ->forParticipant($task)
            ->stream($message);
    }

    /**
     * Send a message to the global AI agent chat.
     * If conversation_id is provided, continue it.
     * If omitted, explicitly start a brand new global conversation.
     */
    public function globalChat(string $message, ?string $conversationId = null): AgentResponse
    {
        $agent = TaskChatAgent::make();

        if ($conversationId !== null) {
            return $agent
                ->continue($conversationId)
                ->prompt($message);
        }

        $newConversationId = $this->conversationStore->storeConversation(
            null,
            null,
            'Global AI Agent Chat'
        );

        return $agent
            ->continue($newConversationId)
            ->prompt($message);
    }

    /**
     * Queue a message to the global AI agent chat.
     * If conversation_id is provided, continue it in background.
     * If omitted, explicitly start a brand new global conversation and queue it.
     */
    public function queueGlobalChat(string $message, ?string $conversationId = null): QueuedAgentResponse
    {
        $agent = TaskChatAgent::make();

        if ($conversationId !== null) {
            return $agent
                ->continue($conversationId)
                ->queue($message);
        }

        $newConversationId = $this->conversationStore->storeConversation(
            null,
            null,
            'Global AI Agent Chat'
        );

        return $agent
            ->continue($newConversationId)
            ->queue($message);
    }

    /**
     * Stream a response from the global AI agent chat.
     * If conversation_id is provided, continue it.
     * If omitted, explicitly start a brand new global conversation stream.
     */
    public function globalStreamChat(string $message, ?string $conversationId = null): \Laravel\Ai\Responses\StreamableAgentResponse
    {
        $agent = TaskChatAgent::make();

        if ($conversationId !== null) {
            return $agent
                ->continue($conversationId)
                ->stream($message);
        }

        $newConversationId = $this->conversationStore->storeConversation(
            null,
            null,
            'Global AI Agent Chat'
        );

        return $agent
            ->continue($newConversationId)
            ->stream($message);
    }

    /**
     * Verify that the given conversation_id belongs to the specified task.
     *
     * @throws ValidationException
     */
    protected function verifyConversationBelongsToTask(Task $task, string $conversationId): void
    {
        $belongsToTask = $task->conversations()
            ->where('id', $conversationId)
            ->exists();

        if (! $belongsToTask) {
            throw ValidationException::withMessages([
                'conversation_id' => ['The selected conversation_id does not belong to this task.'],
            ]);
        }
    }
}
