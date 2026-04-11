<?php

namespace App\Http\Resources;

use App\Http\Resources\AnsiblePlaybookResource;
use Illuminate\Http\Resources\Json\JsonResource;

class TemplateProviderConfigurationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                   => $this->id,
            'template_version_id'  => $this->template_version_id,
            'name'                 => $this->name,
            'applies_to_providers' => $this->applies_to_providers ?? [],
            'terraform_module'     => $this->whenLoaded('terraformModule', function () {
                return $this->terraformModule
                    ? (new TemplateTerraformModuleResource($this->terraformModule))->toArray(request())
                    : null;
            }),
            'playbooks'           => AnsiblePlaybookResource::collection(
                $this->whenLoaded('playbooks'),
            ),
            'created_at'           => $this->created_at,
            'updated_at'           => $this->updated_at,
        ];
    }
}
