<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'activity_status' => $this->activity_status,
            'priority' => $this->priority,
            'is_habit' => $this->is_habit,
            'streak' => $this->is_habit ? $this->currentStreak() : 0,
            'due_at' => $this->due_at,
            'start_at' => $this->start_at,
            'completed_at' => $this->completed_at,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),
        ];
    }
}
