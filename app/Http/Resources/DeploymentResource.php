<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DeploymentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                     => $this->id,
            'environment_id'         => $this->environment_id,
            'deployment_template_id' => $this->deployment_template_id,
            'provider_id'            => $this->provider_id,
            'provider_credential_id' => $this->provider_credential_id,
            'region'                 => $this->region,
            'environment_type'       => $this->environment_type,
            'name'                   => $this->name,
            'status'                 => $this->status,
            'created_at'             => $this->created_at,
            'updated_at'             => $this->updated_at,
        ];
    }
}
