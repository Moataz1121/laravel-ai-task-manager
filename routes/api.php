<?php

use App\Http\Controllers\AiTestController;
use App\Http\Controllers\AnalyzeTaskController;
use App\Http\Controllers\TaskChatController;
use App\Http\Controllers\TaskConversationController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::get('/ai-test', AiTestController::class);
Route::apiResource('tasks', TaskController::class);
Route::post('/tasks/{task}/analyze', AnalyzeTaskController::class);

Route::get('/tasks/{task}/conversations', [TaskConversationController::class, 'index']);
Route::post('/tasks/{task}/conversations', [TaskConversationController::class, 'store']);
Route::post('/tasks/{task}/chat', TaskChatController::class);
