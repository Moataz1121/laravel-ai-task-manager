# Laravel AI Task Manager

An API-only Laravel application integrating the official `laravel/ai` SDK with Google Gemini (`gemini-3.1-flash-lite`) for intelligent task management, structured analysis, multi-thread chat conversations, real-time Server-Sent Events (SSE) streaming, and autonomous AI Agent tool calls.

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

---

## API Documentation & cURL Reference

### 1. Test AI Connectivity
```bash
curl -X GET http://127.0.0.1:8000/api/ai-test \
  -H "Accept: application/json"
```

### 2. Tasks CRUD
```bash
# Create Task
curl -X POST http://127.0.0.1:8000/api/tasks \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"title": "OAuth2 Auth", "description": "Add Google login", "status": "in_progress", "priority": "high"}'

# List Tasks
curl -X GET http://127.0.0.1:8000/api/tasks -H "Accept: application/json"

# Show Single Task
curl -X GET http://127.0.0.1:8000/api/tasks/1 -H "Accept: application/json"

# Update Task
curl -X PUT http://127.0.0.1:8000/api/tasks/1 -H "Content-Type: application/json" -d '{"status": "completed"}'

# Delete Task
curl -X DELETE http://127.0.0.1:8000/api/tasks/1 -H "Accept: application/json"
```

---

### 3. Analyze Task with AI
```bash
curl -X POST http://127.0.0.1:8000/api/tasks/1/analyze -H "Accept: application/json"
```

---

### 4. Global AI Agent Endpoints (Project-Wide Tasks Management)

#### A. Global AI Agent Chat (Create Task / List Tasks / Update Any Task)
```bash
curl -X POST http://127.0.0.1:8000/api/agent/chat \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "message": "Create a high priority task to implement Stripe payments."
  }'
```

```bash
curl -X POST http://127.0.0.1:8000/api/agent/chat \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "message": "Show me all pending tasks."
  }'
```

```bash
curl -X POST http://127.0.0.1:8000/api/agent/chat \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "message": "Change task 5 status to completed."
  }'
```

#### B. Global AI Agent Chat Streaming (SSE Stream)
```bash
curl -N -X POST http://127.0.0.1:8000/api/agent/chat/stream \
  -H "Content-Type: application/json" \
  -H "Accept: text/event-stream" \
  -d '{
    "message": "Stream all high priority tasks."
  }'
```

---

### 5. Task-Scoped AI Chat Endpoints (Bound to Task #1)

```bash
# Task-Scoped AI Chat
curl -X POST http://127.0.0.1:8000/api/tasks/1/chat \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"message": "Change this task status to completed."}'

# Task-Scoped AI Chat Stream
curl -N -X POST http://127.0.0.1:8000/api/tasks/1/chat/stream \
  -H "Content-Type: application/json" \
  -H "Accept: text/event-stream" \
  -d '{"message": "Explain how to implement this task."}'
```

---

## Testing

Run the full PHPUnit test suite covering Tasks CRUD, AI Analysis, Multi-Thread Chat, Real-Time Streaming, Task-Scoped Security, and Global AI Agent Tools:

```bash
php artisan test
```
