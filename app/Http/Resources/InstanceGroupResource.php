<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InstanceGroupResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'cluster_id' => $this->cluster_id,
            'instance_type_id' => $this->instance_type_id,
            'instance_type_name' => $this->instanceType->name ?? null,
            'role' => $this->role,
            'quantity' => $this->quantity,
            'metadata' => $this->metadata_json,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
