<?php

namespace App\Services;

use App\Models\Organization;
use App\Repositories\ProviderRepository;

class SeedOrganizationDefaultProvidersService
{
    private const DEFAULT_PROVIDERS = [
        [
            'name'                => 'AWS',
            'slug'                => 'aws',
            'default_module_slug' => 'aws_nvflare',
            'type'                => 'AWS',
            'description'         => 'Amazon Web Services — public cloud provider.',
        ],
        [
            'name'                => 'GCP',
            'slug'                => 'gcp',
            'default_module_slug' => 'gcp_gke',
            'type'                => 'GCP',
            'description'         => 'Google Cloud Platform — public cloud provider.',
        ],
        [
            'name'                => 'Local',
            'slug'                => 'local',
            'default_module_slug' => null,
            'type'                => 'LOCAL',
            'description'         => 'Local machine — uses the Docker socket to run containers directly on the host.',
        ],
    ];

    public function __construct(
        private ProviderRepository $providerRepository,
    ) {}

    public function execute(Organization $organization): void
    {
        foreach (self::DEFAULT_PROVIDERS as $provider) {
            $this->providerRepository->create([
                'organization_id'     => $organization->id,
                'name'                => $provider['name'],
                'slug'                => $provider['slug'],
                'default_module_slug' => $provider['default_module_slug'],
                'description'         => $provider['description'],
                'type'                => $provider['type'],
                'status'              => 'ACTIVE',
                'health_status'       => 'HEALTHY',
            ]);
        }
    }
}
