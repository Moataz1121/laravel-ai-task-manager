# Laravel AI Task Manager

An API-only Laravel application integrating the official `laravel/ai` SDK with Google Gemini (`gemini-3.1-flash-lite`) for intelligent task management, structured analysis, multi-thread chat conversations, real-time Server-Sent Events (SSE) streaming, autonomous AI Agent tool calls, and background queue processing.

---

## Features Implemented

### PART 1 — Project Setup & AI Test Endpoint
- Integrated `laravel/ai` package with Google Gemini provider.
- Created `AiTestController` & `AiTestAgent` verifying connectivity via `GET /api/ai-test`.

### PART 2 — Tasks CRUD
- RESTful CRUD endpoints for `Task` resource (`GET`, `POST`, `PUT`, `DELETE /api/tasks`).
- Enums: `TaskStatus` (`pending`, `in_progress`, `completed`), `TaskPriority` (`low`, `medium`, `high`).
- Input validation via Laravel Form Requests (`StoreTaskRequest`, `UpdateTaskRequest`).
- Structured response serialization via `TaskResource`.

### PART 3 & PART 4 — AI Task Analyzer & Structured Output Persistence
- `TaskAnalyzerAgent` implementing `Laravel\Ai\Contracts\HasStructuredOutput`.
- Schema definition:
  - `summary`: string
  - `complexity`: `low` | `medium` | `high`
  - `estimated_hours`: integer
  - `steps`: array of strings
  - `risks`: array of strings
- `TaskAnalysis` model and database table (`task_analyses`) linked to `Task` via `hasOne` relationship.
- Validates structured AI output with `Validator` and persists/updates records in database via `POST /api/tasks/{task}/analyze`.

### PART 5 — AI Chat & Multiple Conversations
- `TaskChatAgent` implementing `Conversational` and `RemembersConversations`.
- Contextualized system instructions containing task title, description, status, and priority.
- Supports **multiple independent conversation threads per task** using `agent_conversations` and `agent_conversation_messages`.
- Ownership verification preventing cross-task conversation access.
- Endpoints:
  - `GET /api/tasks/{task}/conversations` — List conversations belonging to a task.
  - `POST /api/tasks/{task}/conversations` — Explicitly create a new conversation thread.
  - `POST /api/tasks/{task}/chat` — Continue an existing conversation or start a new thread.

### PART 6 — Real-time AI Streaming
- Stream AI chat responses in real-time using Laravel AI SDK `StreamableAgentResponse`.
- Streams Server-Sent Events (`text/event-stream; charset=utf-8`) compatible with frontend EventSource, React, and Flutter clients.
- Maintains complete conversation history and auto-saves the full aggregated AI response to database upon stream completion.
- Endpoint: `POST /api/tasks/{task}/chat/stream`.

### PART 7 — AI Tools & Global AI Agent
- Equipped `TaskChatAgent` with tool capabilities via `Laravel\Ai\Contracts\HasTools`.
- Created dedicated Tool classes:
  - `ListTasksTool` (`list_tasks`): Filter database tasks by status and priority.
  - `GetTaskTool` (`get_task`): Fetch specific task details by integer ID.
  - `CreateTaskTool` (`create_task`): Insert new tasks into the database.
  - `UpdateTaskTool` (`update_task`): Modify existing task records.
- Added **Global AI Agent Endpoints** (`POST /api/agent/chat` & `POST /api/agent/chat/stream`) allowing project-wide AI task management without specifying `{task}` in the URL.
- Preserved **Task-scoped endpoints** (`POST /api/tasks/{task}/chat`) with security boundary enforcement preventing modification of unrelated tasks inside scoped conversations.

### PART 8 — Asynchronous Queue Processing
- Offloads long-running AI operations out of the HTTP request lifecycle so endpoints return immediately.
- **Queued Global AI Agent Endpoint** (`POST /api/agent/chat/queue`):
  - Uses native Laravel AI SDK `$agent->queue($message)` functionality (`InvokeAgent` job).
  - Preserves conversation history, tools (`list_tasks`, `get_task`, `create_task`, `update_task`), and execution context.
- **Queued Task Analysis Endpoint** (`POST /api/tasks/{task}/analyze/queue`):
  - Dispatches `AnalyzeTaskJob` to process task analysis asynchronously in the background.
  - Reuses `TaskAnalysisService` and `TaskAnalyzerAgent` for structured JSON output validation and database persistence.
- **Queue Execution & Retries**:
  - Uses Laravel's `database` queue driver for local development.
  - Handles retries (`tries = 3`), timeouts (`timeout = 60`), and logs failed jobs to `failed_jobs` table.

---

## Comparison of AI Execution Modes

| Feature | Synchronous (`POST .../chat`) | Real-Time Stream (`POST .../chat/stream`) | Queued (`POST .../chat/queue`) |
|---|---|---|---|
| **Response Time** | Waits for full AI completion | Streams tokens instantly (SSE) | Returns immediately (`status: queued`) |
| **HTTP Payload** | Single JSON response object | `text/event-stream` chunks | Immediate status confirmation |
| **Execution Context** | Synchronous HTTP worker | Synchronous HTTP worker | Background Queue Worker (`queue:work`) |
| **Use Case** | Quick answers / instant UI update | Live typing / interactive chat | Heavy operations / background tasks |

---

## Architecture Diagram

```
                 HTTP LAYER (Controllers)
┌───────────────────────────┬────────────────────────────────┬────────────────────────────┐
│ QueueGlobalAgentChatCtrl  │   QueueAnalyzeTaskController   │   TaskChatController / etc │
└─────────────┬─────────────┴───────────────┬────────────────┴──────────────┬─────────────┘
              │                             │                               │
              ▼                             ▼                               ▼
  Laravel AI SDK $agent->queue()     AnalyzeTaskJob::dispatch()      Synchronous / Stream
              │                             │                               │
              └───────────────┬─────────────┘                               │
                              ▼                                             │
                    Laravel Queue Worker                                    │
                   (php artisan queue:work)                                 │
                              │                                             │
                              ▼                                             ▼
                 ┌──────────────────────────┐                    ┌─────────────────────┐
                 │ TaskChatAgent / Tools    │                    │ TaskChatService /   │
                 │ TaskAnalysisService      │                    │ TaskAnalysisService │
                 └────────────┬─────────────┘                    └──────────┬──────────┘
                              │                                             │
                              └──────────────────────┬──────────────────────┘
                                                     ▼
                                          Eloquent / MySQL Database
```

---

## API Documentation & cURL Reference

### 1. Queued Global AI Agent Endpoint
```bash
curl -X POST http://127.0.0.1:8000/api/agent/chat/queue \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "message": "Analyze my pending tasks and suggest what I should work on next."
  }'
```
**Immediate Response (`200 OK`):**
```json
{
  "status": "queued",
  "message": "AI request has been queued."
}
```

---

### 2. Queued Task Analysis Endpoint
```bash
curl -X POST http://127.0.0.1:8000/api/tasks/1/analyze/queue \
  -H "Accept: application/json"
```
**Immediate Response (`200 OK`):**
```json
{
  "status": "queued",
  "message": "Task analysis has been queued."
}
```

---

### 3. How to Start the Queue Worker

To process queued AI jobs locally, run the Laravel queue worker in your terminal:

```bash
php artisan queue:work
```

To monitor failed jobs or retry them:
```bash
# View failed jobs
php artisan queue:failed

# Retry all failed jobs
php artisan queue:retry all
```

---

### 4. Synchronous & Streaming AI Reference

#### Synchronous Global Agent Chat
```bash
curl -X POST http://127.0.0.1:8000/api/agent/chat \
  -H "Content-Type: application/json" \
  -d '{"message": "Create a high priority task for Stripe payments."}'
```

#### Real-Time SSE AI Stream
```bash
curl -N -X POST http://127.0.0.1:8000/api/agent/chat/stream \
  -H "Content-Type: application/json" \
  -H "Accept: text/event-stream" \
  -d '{"message": "Stream all pending tasks."}'
```

---

## Testing

Run the full PHPUnit test suite covering Tasks CRUD, AI Analysis, Multi-Thread Chat, Real-Time Streaming, Task-Scoped Security, Global AI Agent Tools, and Queued AI Operations:

```bash
php artisan test
```
