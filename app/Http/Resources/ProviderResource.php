<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProviderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                  => $this->id,
            'organization_id'     => (int) $this->organization_id,
            'name'                => $this->name,
            'slug'                => $this->slug,
            'default_module_slug' => $this->default_module_slug,
            'description'         => $this->description,
            'type'                => $this->type,
            'status'              => $this->status,
            'health_status'       => $this->health_status,
            'health_message'      => $this->health_message,
            'last_health_check_at'=> $this->last_health_check_at,
            'credentials_count'   => $this->credentials_count ?? $this->credentials()->count(),
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }
}
