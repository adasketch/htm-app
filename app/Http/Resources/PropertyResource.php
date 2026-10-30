<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'timezone' => $this->timezone,
            'address' => $this->address,
            'departments_count' => $this->whenCounted('departments'),
            'departments' => DepartmentResource::collection($this->whenLoaded('departments')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
