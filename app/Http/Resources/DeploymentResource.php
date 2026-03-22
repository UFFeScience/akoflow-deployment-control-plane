<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\InstanceGroupResource;

class ClusterResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'environment_id' => $this->environment_id,
            'cluster_template_id' => $this->cluster_template_id,
            'provider_id' => $this->provider_id,
            'provider_credential_id' => $this->provider_credential_id,
            'region' => $this->region,
            'environment_type' => $this->environment_type,
            'name' => $this->name,
            'status' => $this->status,
            'instance_groups' => InstanceGroupResource::collection($this->whenLoaded('instanceGroups')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
