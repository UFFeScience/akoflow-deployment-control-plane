<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProviderCredentialResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'provider_id' => $this->provider_id,
            'name'        => $this->name,
            'description' => $this->description,
            'is_active'   => $this->is_active,
            'values'      => ProviderCredentialValueResource::collection($this->whenLoaded('values')),
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
        ];
    }
}
