<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InstanceLogResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'provisioned_instance_id' => $this->provisioned_instance_id,
            'level' => $this->level,
            'message' => $this->message,
            'created_at' => $this->created_at,
        ];
    }
}
