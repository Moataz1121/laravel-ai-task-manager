<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskAnalysis extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'task_id',
        'summary',
        'complexity',
        'estimated_hours',
        'steps',
        'risks',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estimated_hours' => 'integer',
            'steps' => 'array',
            'risks' => 'array',
        ];
    }

    /**
     * Get the task associated with the analysis.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}

