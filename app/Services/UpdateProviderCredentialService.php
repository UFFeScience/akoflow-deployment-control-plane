<?php

namespace App\Services;

use App\Models\ProviderCredential;
use App\Repositories\ProviderCredentialRepository;

class UpdateProviderCredentialService
{
    public function __construct(
        private ProviderCredentialRepository $credentials,
    ) {}

    public function handle(ProviderCredential $credential, array $data): ProviderCredential
    {
        return $this->credentials->updateWithValues(
            $credential,
            [
                'name'                  => $data['name'] ?? null,
                'description'           => $data['description'] ?? null,
                'is_active'             => $data['is_active'] ?? null,
                'health_check_template' => $data['health_check_template'] ?? null,
            ],
            $data['values'] ?? [],
        );
    }
}
