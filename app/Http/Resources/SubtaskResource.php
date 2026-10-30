<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubtaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'title' => $this->title,
            'description' => $this->description,
            'is_completed' => (bool) $this->is_completed,
            'creator' => new TaskParticipantResource($this->whenLoaded('creator')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
