<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'property_id' => $this->property_id,
            'name' => $this->name,
            'is_default' => $this->whenPivotLoaded('department_user', function () {
                return (bool) $this->pivot->is_default;
            }),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
