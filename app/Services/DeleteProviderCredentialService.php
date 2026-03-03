<?php

namespace App\Services;

use App\Repositories\ProviderCredentialRepository;

class DeleteProviderCredentialService
{
    public function __construct(private ProviderCredentialRepository $credentials)
    {
    }

    public function handle(string $providerId, string $credentialId): void
    {
        $this->credentials->deleteByProviderAndId($providerId, $credentialId);
    }
}
