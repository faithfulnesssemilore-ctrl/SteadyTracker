<?php

namespace App\Http\Resources;

use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Activity */
class ActivityResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'activity_status' => $this->activity_status,
            'priority' => $this->priority,
            'due_at' => $this->due_at,
            'start_at' => $this->start_at,
            'completed_at' => $this->completed_at,
        ];
    }
}
