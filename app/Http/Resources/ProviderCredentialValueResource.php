<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProviderCredentialValueResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                     => $this->id,
            'provider_credential_id' => $this->provider_credential_id,
            'field_key'              => $this->field_key,
            // mask sensitive fields — only show if explicitly requested
            'field_value'            => null,
        ];
    }
}
