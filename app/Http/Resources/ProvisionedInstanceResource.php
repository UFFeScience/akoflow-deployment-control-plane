<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProvisionedInstanceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'cluster_id' => $this->cluster_id,
            'instance_type_id' => $this->instance_type_id,
            'provider_instance_id' => $this->provider_instance_id,
            'role' => $this->role,
            'status' => $this->status,
            'health_status' => $this->health_status,
            'last_health_check_at' => $this->last_health_check_at,
            'public_ip' => $this->public_ip,
            'private_ip' => $this->private_ip,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
