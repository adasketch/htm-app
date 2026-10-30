<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskParticipantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $participant = [
            'id' => $this->id,
            'name' => $this->name,
        ];

        if ($request->user()?->hasPrivilege('users.view')) {
            $participant['email'] = $this->email;
        }

        return $participant;
    }
}
