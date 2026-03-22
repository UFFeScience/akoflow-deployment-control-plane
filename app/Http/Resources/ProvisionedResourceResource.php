<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProvisionedResourceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                           => $this->id,
            'deployment_id'                => $this->deployment_id,
            'provisioned_resource_type_id' => $this->provisioned_resource_type_id,
            'resource_type'                => $this->whenLoaded('resourceType', fn() => [
                'id'                          => $this->resourceType->id,
                'slug'                        => $this->resourceType->slug,
                'name'                        => $this->resourceType->name,
                'provider_resource_identifier' => $this->resourceType->provider_resource_identifier,
                'kind'                        => $this->resourceType->relationLoaded('kind') ? [
                    'id'   => $this->resourceType->kind->id,
                    'slug' => $this->resourceType->kind->slug,
                    'name' => $this->resourceType->kind->name,
                ] : null,
            ]),
            'provider_resource_id'         => $this->provider_resource_id,
            'name'                         => $this->name,
            'status'                       => $this->status,
            'health_status'                => $this->health_status,
            'last_health_check_at'         => $this->last_health_check_at,
            'public_ip'                    => $this->public_ip,
            'private_ip'                   => $this->private_ip,
            'metadata_json'                => $this->metadata_json,
            'created_at'                   => $this->created_at,
            'updated_at'                   => $this->updated_at,
        ];
    }
}
