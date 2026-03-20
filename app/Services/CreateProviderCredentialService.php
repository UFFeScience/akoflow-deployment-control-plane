<?php

namespace App\Services;

use App\Models\ProviderCredential;
use App\Repositories\ProviderCredentialRepository;
use App\Repositories\ProviderRepository;
use Illuminate\Support\Str;

class CreateProviderCredentialService
{
    public function __construct(
        private ProviderRepository $providers,
        private ProviderCredentialRepository $credentials,
    ) {
    }

    public function handle(string $providerId, array $data): ProviderCredential
    {
        $provider = $this->providers->findOrFailById($providerId);

        $slug = $data['slug'] ?? Str::slug($data['name']);

        return $this->credentials->createWithValues(
            [
                'provider_id' => $provider->id,
                'name'        => $data['name'],
                'slug'        => $slug,
                'description' => $data['description'] ?? null,
                'is_active'   => $data['is_active'] ?? true,
            ],
            $data['values'] ?? [],
        );
    }
}
