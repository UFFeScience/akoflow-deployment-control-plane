<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProviderCredentialResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                    => $this->id,
            'provider_id'           => $this->provider_id,
            'name'                  => $this->name,
            'slug'                  => $this->slug ?? null,
            'description'           => $this->description,
            'is_active'             => $this->is_active,
            'health_check_template' => $this->health_check_template,
            'health_status'         => $this->health_status,
            'health_message'        => $this->health_message,
            'last_health_check_at'  => $this->last_health_check_at,
            'health_logs'          => ProviderCredentialHealthLogResource::collection($this->whenLoaded('healthLogs')),
            'values'               => ProviderCredentialValueResource::collection($this->whenLoaded('values')),
            'created_at'           => $this->created_at,
            'updated_at'           => $this->updated_at,
        ];
    }
}
