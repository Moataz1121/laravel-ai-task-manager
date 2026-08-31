# Laravel AI Task Manager

An API-only Laravel application integrating the official `laravel/ai` SDK with Google Gemini (`gemini-3.1-flash-lite`) for intelligent task management, structured analysis, multi-thread chat conversations, and real-time Server-Sent Events (SSE) streaming.

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

---

## API Documentation & cURL Reference

### 1. Test AI Connectivity
```bash
curl -X GET http://127.0.0.1:8000/api/ai-test \
  -H "Accept: application/json"
```

### 2. Create a Task
```bash
curl -X POST http://127.0.0.1:8000/api/tasks \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "Implement OAuth2 Authentication",
    "description": "Add Google and GitHub OAuth logins using Socialite",
    "status": "in_progress",
    "priority": "high"
  }'
```

### 3. List All Tasks
```bash
curl -X GET http://127.0.0.1:8000/api/tasks \
  -H "Accept: application/json"
```

### 4. Fetch a Single Task
```bash
curl -X GET http://127.0.0.1:8000/api/tasks/1 \
  -H "Accept: application/json"
```

### 5. Update a Task
```bash
curl -X PUT http://127.0.0.1:8000/api/tasks/1 \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "status": "completed"
  }'
```

### 6. Delete a Task
```bash
curl -X DELETE http://127.0.0.1:8000/api/tasks/1 \
  -H "Accept: application/json"
```

---

### 7. Analyze a Task with AI (Structured JSON + DB Save)
```bash
curl -X POST http://127.0.0.1:8000/api/tasks/1/analyze \
  -H "Accept: application/json"
```
**Example Response:**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "task_id": 1,
    "summary": "Implement OAuth2 authentication via Socialite.",
    "complexity": "medium",
    "estimated_hours": 12,
    "steps": [
      "Install laravel/socialite package",
      "Configure OAuth client keys",
      "Add login and callback routes"
    ],
    "risks": [
      "Handling edge cases when user email is hidden by provider"
    ],
    "created_at": "2026-08-31T14:00:00.000000Z",
    "updated_at": "2026-08-31T14:00:00.000000Z"
  }
}
```

---

### 8. Explicitly Create a New Conversation for a Task
```bash
curl -X POST http://127.0.0.1:8000/api/tasks/1/conversations \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "Architecture Discussion"
  }'
```

### 9. List All Conversations for a Task
```bash
curl -X GET http://127.0.0.1:8000/api/tasks/1/conversations \
  -H "Accept: application/json"
```

---

### 10. AI Task Chat (Normal JSON Response)
```bash
curl -X POST http://127.0.0.1:8000/api/tasks/1/chat \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "conversation_id": "0194a3b8-7c82-7000-8000-123456789abc",
    "message": "How should I start implementing security for this task?"
  }'
```

---

### 11. Real-Time AI Chat Streaming (Server-Sent Events)
```bash
curl -N -X POST http://127.0.0.1:8000/api/tasks/1/chat/stream \
  -H "Content-Type: application/json" \
  -H "Accept: text/event-stream" \
  -d '{
    "conversation_id": "0194a3b8-7c82-7000-8000-123456789abc",
    "message": "Explain how I should test this implementation step by step."
  }'
```
**Streamed SSE Data Output:**
```http
data: {"type":"stream_start","provider":"gemini","model":"gemini-3.1-flash-lite"}

data: {"type":"text_delta","content":"To "}

data: {"type":"text_delta","content":"test "}

data: {"type":"text_delta","content":"this "}

data: {"type":"text_delta","content":"feature..."}

data: {"type":"stream_end"}

data: [DONE]
```

---

## Testing

Run the full PHPUnit test suite covering Tasks CRUD, AI Analysis, Multi-Thread Chat, and Real-time Streaming:

```bash
php artisan test
```
