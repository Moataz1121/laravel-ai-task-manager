<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskAnalysisResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'summary' => $this->summary,
            'complexity' => $this->complexity,
            'estimated_hours' => $this->estimated_hours,
            'steps' => $this->steps,
            'risks' => $this->risks,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

