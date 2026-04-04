<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProviderCredentialHealthLogResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'health_status'  => $this->health_status,
            'health_message' => $this->health_message,
            'checked_at'     => $this->checked_at,
        ];
    }
}
