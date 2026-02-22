<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InstanceTypeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'provider_id' => $this->provider_id,
            'name' => $this->name,
            'vcpus' => $this->vcpus,
            'memory_mb' => $this->memory_mb,
            'gpu_count' => $this->gpu_count,
            'storage_default_gb' => $this->storage_default_gb,
            'network_bandwidth' => $this->network_bandwidth,
            'region' => $this->region,
            'status' => $this->status,
            'is_active' => (bool)$this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
