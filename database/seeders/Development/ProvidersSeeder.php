<?php

namespace Database\Seeders\Development;

use App\Models\Provider;
use Illuminate\Database\Seeder;

class ProvidersSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            ['name' => 'AWS', 'type' => 'CLOUD'],
            ['name' => 'GCP', 'type' => 'CLOUD'],
            ['name' => 'HPC', 'type' => 'HPC'],
        ];

        foreach ($providers as $provider) {
            Provider::firstOrCreate(
                ['name' => $provider['name']],
                [
                    'type' => $provider['type'],
                    'status' => 'ACTIVE',
                    'health_status' => 'HEALTHY',
                ]
            );
        }
    }
}
