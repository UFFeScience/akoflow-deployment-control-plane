<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EnvironmentTemplateVersionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                     => $this->id,
            'template_id'            => $this->template_id,
            'version'                => $this->version,
            'definition_json'        => $this->definition_json,
            'is_active'              => (bool) $this->is_active,
            'provider_configurations' => TemplateProviderConfigurationResource::collection(
                $this->whenLoaded('providerConfigurations'),
            ),
            'created_at'             => $this->created_at,
        ];
    }
}
