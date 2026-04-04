<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProviderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                       => $this->id,
            'organization_id'          => (int) $this->organization_id,
            'name'                     => $this->name,
            'slug'                     => $this->slug,
            'default_module_slug'      => $this->default_module_slug,
            'description'              => $this->description,
            'type'                     => $this->type,
            'status'                   => $this->status,
            'credentials_count'        => $this->credentials_count ?? $this->credentials()->count(),
            'healthy_credentials_count'=> (int) ($this->healthy_credentials_count ?? 0),
            'created_at'               => $this->created_at,
            'updated_at'               => $this->updated_at,
        ];
    }
}
