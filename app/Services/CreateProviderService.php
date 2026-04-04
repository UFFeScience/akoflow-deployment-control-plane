<?php

namespace App\Services;

use App\Repositories\ProviderRepository;
use App\Repositories\ProviderVariableSchemaRepository;
use App\Models\Provider;
use Illuminate\Support\Str;

class CreateProviderService
{
    public function __construct(
        private ProviderRepository $providers,
        private ProviderVariableSchemaRepository $schemas,
    ) {}

    public function handle(array $data): Provider
    {
        $schemas = $data['variable_schemas'] ?? [];
        unset($data['variable_schemas']);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        /** @var Provider $provider */
        $provider = $this->providers->create($data);

        foreach ($schemas as $index => $schema) {
            $this->schemas->createForProvider($provider->slug, [
                'section'       => $schema['section'],
                'name'          => $schema['name'],
                'label'         => $schema['label'],
                'description'   => $schema['description'] ?? null,
                'type'          => $schema['type'],
                'required'      => $schema['required'] ?? false,
                'is_sensitive'  => $schema['is_sensitive'] ?? false,
                'position'      => $schema['position'] ?? $index,
                'options_json'  => isset($schema['options']) ? json_encode($schema['options']) : null,
                'default_value' => $schema['default_value'] ?? null,
            ]);
        }

        return $provider;
    }
}
