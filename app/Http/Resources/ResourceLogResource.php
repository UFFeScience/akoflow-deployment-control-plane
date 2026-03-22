<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ResourceLogResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                      => $this->id,
            'provisioned_resource_id' => $this->provisioned_resource_id,
            'level'                   => $this->level,
            'message'                 => $this->message,
            'created_at'              => $this->created_at,
        ];
    }
}
