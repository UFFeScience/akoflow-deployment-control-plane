<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->user_id,
            'organization_id' => $this->organization_id,
            'role' => $this->role,
            'user' => new UserResource($this->whenLoaded('user')),
            'joined_at' => $this->created_at,
        ];
    }
}
