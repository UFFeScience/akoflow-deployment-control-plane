<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EnvironmentTemplateResource extends JsonResource
{
    public function toArray($request): array
    {
        $versions = $this->whenLoaded('versions');

        $activeVersion = null;
        if ($this->relationLoaded('versions')) {
            $activeVersion = $this->versions->firstWhere('is_active', true);
        }

        return [
            'id'                    => $this->id,
            'name'                  => $this->name,
            'slug'                  => $this->slug,
            'runtime_type'          => $this->runtime_type,
            'description'           => $this->description,
            'is_public'             => (bool) $this->is_public,
            'owner_organization_id' => $this->owner_organization_id,
            'versions_count'        => $this->relationLoaded('versions') ? $this->versions->count() : null,
            'active_version'        => $activeVersion ? new EnvironmentTemplateVersionResource($activeVersion) : null,
            'versions'              => $this->whenLoaded('versions', fn() =>
                EnvironmentTemplateVersionResource::collection($this->versions)
            ),
            'created_at'            => $this->created_at,
            'updated_at'            => $this->updated_at,
        ];
    }
}

