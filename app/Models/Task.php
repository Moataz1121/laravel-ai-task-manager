<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Laravel\Ai\Models\Conversation;

class Task extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
        ];
    }

    /**
     * Get the AI analysis associated with the task.
     */
    public function analysis(): HasOne
    {
        return $this->hasOne(TaskAnalysis::class);
    }

    /**
     * Get all AI conversations associated with the task.
     */
    public function conversations(): MorphMany
    {
        return $this->morphMany(Conversation::class, 'participant');
    }
}
