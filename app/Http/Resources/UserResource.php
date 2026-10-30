<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'position' => $this->position,
            'email' => $this->email,
            'role' => $this->role,
            'privileges' => $this->privileges(),
            'approval_status' => $this->approval_status,
            'property' => new PropertyResource($this->whenLoaded('property')),
            'default_department' => $this->whenLoaded('departments', function () {
                $department = $this->departments->firstWhere('pivot.is_default', true);

                return $department ? ['id' => $department->id, 'name' => $department->name] : null;
            }),
            'departments' => DepartmentResource::collection($this->whenLoaded('departments')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
