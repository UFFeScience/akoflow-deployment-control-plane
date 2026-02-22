<?php

namespace Database\Seeders\Development;

use App\Models\InstanceType;
use App\Models\Provider;
use Illuminate\Database\Seeder;

class InstanceTypesSeeder extends Seeder
{
    public function run(): void
    {
        $providers = Provider::whereIn('name', ['AWS', 'GCP', 'HPC'])->get()->keyBy('name');
        if ($providers->isEmpty()) {
            return;
        }

        $definitions = [
            'AWS' => [
                [
                    'name' => 't3.medium',
                    'vcpus' => 2,
                    'memory_mb' => 4096,
                    'gpu_count' => 0,
                    'storage_default_gb' => 50,
                    'network_bandwidth' => 'Up to 5 Gbps',
                    'region' => 'us-east-1',
                ],
                [
                    'name' => 'p3.2xlarge',
                    'vcpus' => 8,
                    'memory_mb' => 62464,
                    'gpu_count' => 1,
                    'storage_default_gb' => 100,
                    'network_bandwidth' => 'Up to 10 Gbps',
                    'region' => 'us-east-1',
                ],
                [
                    'name' => 'p3.8xlarge',
                    'vcpus' => 32,
                    'memory_mb' => 249856,
                    'gpu_count' => 4,
                    'storage_default_gb' => 100,
                    'network_bandwidth' => '10 Gbps',
                    'region' => 'us-east-1',
                ],
                [
                    'name' => 'c5.4xlarge',
                    'vcpus' => 16,
                    'memory_mb' => 32768,
                    'gpu_count' => 0,
                    'storage_default_gb' => 200,
                    'network_bandwidth' => 'Up to 10 Gbps',
                    'region' => 'us-west-2',
                ],
            ],
            'GCP' => [
                [
                    'name' => 'a2-highgpu-1g',
                    'vcpus' => 12,
                    'memory_mb' => 87040,
                    'gpu_count' => 1,
                    'storage_default_gb' => 100,
                    'network_bandwidth' => 'Up to 32 Gbps',
                    'region' => 'us-central1',
                ],
                [
                    'name' => 'n1-standard-8',
                    'vcpus' => 8,
                    'memory_mb' => 30720,
                    'gpu_count' => 1,
                    'storage_default_gb' => 100,
                    'network_bandwidth' => 'Up to 10 Gbps',
                    'region' => 'us-east1',
                ],
            ],
            'HPC' => [
                [
                    'name' => 'hpc-node-large',
                    'vcpus' => 64,
                    'memory_mb' => 524288,
                    'gpu_count' => 8,
                    'storage_default_gb' => 1000,
                    'network_bandwidth' => 'Infiniband',
                    'region' => 'on-premise',
                ],
            ],
        ];

        foreach ($definitions as $providerName => $types) {
            $provider = $providers->get($providerName);
            if (! $provider) {
                continue;
            }

            foreach ($types as $type) {
                InstanceType::firstOrCreate(
                    [
                        'provider_id' => $provider->id,
                        'name' => $type['name'],
                        'region' => $type['region'],
                    ],
                    array_merge($type, [
                        'provider_id' => $provider->id,
                        'status' => 'AVAILABLE',
                        'is_active' => true,
                    ])
                );
            }
        }
    }
}
