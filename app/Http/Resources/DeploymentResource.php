<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DeploymentResource extends JsonResource
{
    public function toArray($request): array
    {
        $providerCredentials = $this->whenLoaded('providerCredentials', function () {
            return $this->providerCredentials->map(fn ($pc) => [
                'id'                     => $pc->id,
                'provider_id'            => $pc->provider_id,
                'provider_slug'          => $pc->provider_slug,
                'provider_credential_id' => $pc->provider_credential_id,
            ]);
        });

        return [
            'id'                     => $this->id,
            'environment_id'         => $this->environment_id,
            'deployment_template_id' => $this->deployment_template_id,
            'provider_credentials'   => $providerCredentials,
            'region'                 => $this->region,
            'environment_type'       => $this->environment_type,
            'name'                   => $this->name,
            'status'                 => $this->status,
            'created_at'             => $this->created_at,
            'updated_at'             => $this->updated_at,
        ];
    }
}
