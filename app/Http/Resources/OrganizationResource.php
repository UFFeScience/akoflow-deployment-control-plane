<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'description' => $this->description,
            'creator' => new UserResource($this->whenLoaded('creator')),
            'members_count' => $this->whenLoaded('members', fn () => $this->members->count()),
            'projects_count' => $this->whenLoaded('projects', fn () => $this->projects->count()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
