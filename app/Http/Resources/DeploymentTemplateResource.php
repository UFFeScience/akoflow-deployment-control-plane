<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DeploymentTemplateResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'template_version_id' => $this->template_version_id,
            'custom_parameters_json' => $this->custom_parameters_json,
            'created_at' => $this->created_at,
        ];
    }
}
