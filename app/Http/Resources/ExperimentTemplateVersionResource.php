<?php

namespace App\Http\Resources;

use App\Http\Resources\TemplateTerraformModuleResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ExperimentTemplateVersionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                  => $this->id,
            'template_id'         => $this->template_id,
            'version'             => $this->version,
            'definition_json'     => $this->definition_json,
            'is_active'           => (bool) $this->is_active,
            'terraform_modules'   => TemplateTerraformModuleResource::collection(
                $this->whenLoaded('terraformModules'),
            ),
            'created_at'          => $this->created_at,
        ];
    }
}
