<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProviderVariableSchemaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'provider_slug' => $this->provider_slug,
            'section'       => $this->section,
            'name'          => $this->name,
            'label'         => $this->label,
            'description'   => $this->description,
            'type'          => $this->type,
            'required'      => $this->required,
            'is_sensitive'  => $this->is_sensitive,
            'position'      => $this->position,
            'options'       => $this->options,
            'default_value' => $this->default_value,
        ];
    }
}
