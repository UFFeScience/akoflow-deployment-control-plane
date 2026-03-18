<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EnvironmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'environment_template_version_id' => $this->environment_template_version_id,
            'template_name' => $this->whenLoaded('templateVersion', fn() =>
                optional(optional($this->templateVersion)->template)->name
            ),
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'execution_mode' => $this->execution_mode,
            'configuration_json' => $this->configuration_json,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
