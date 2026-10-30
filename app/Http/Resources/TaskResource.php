<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'property_id' => $this->property_id,
            'department_id' => $this->department_id,
            'assigned_to' => $this->assigned_to,
            'title' => $this->title,
            'description' => $this->description,
            'task_type' => $this->task_type,
            'priority' => $this->priority,
            'status' => $this->status,
            'progress_percentage' => (int) $this->progress_percentage,
            'completion_ready' => $this->when(
                isset($this->checklist_items_count),
                fn (): bool => (int) $this->progress_percentage === 100
                    && ($this->task_type !== 'checklist'
                        || ((int) $this->checklist_items_count > 0
                            && (int) $this->completed_checklist_items_count === (int) $this->checklist_items_count))
                    && (int) $this->completed_subtasks_count === (int) $this->subtasks_count,
            ),
            'due_date' => $this->due_date,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'property' => new PropertyResource($this->whenLoaded('property')),
            'department' => new DepartmentResource($this->whenLoaded('department')),
            'creator' => new TaskParticipantResource($this->whenLoaded('creator')),
            'assignee' => new TaskParticipantResource($this->whenLoaded('assignee')),
            'subtasks' => SubtaskResource::collection($this->whenLoaded('subtasks')),
            'checklist_items' => TaskChecklistItemResource::collection($this->whenLoaded('checklistItems')),
            'comments' => TaskCommentResource::collection($this->whenLoaded('comments')),
            'attachments' => TaskAttachmentResource::collection($this->whenLoaded('attachments')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
