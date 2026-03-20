<?php

namespace Database\Seeders\Development;

use App\Models\Provider;
use App\Models\ProviderCredential;
use App\Models\ProviderCredentialValue;
use Illuminate\Database\Seeder;

class ProviderCredentialsSeeder extends Seeder
{
    public function run(): void
    {
        $credentials = [
            // ─── AWS ──────────────────────────────────────────────────────────
            [
                'provider_slug' => 'aws',
                'name'          => 'AWS Demo Credentials',
                'slug'          => 'aws-demo',
                'description'   => 'Dummy AWS credentials for development/demo purposes.',
                'is_active'     => true,
                'values'        => [
                    'aws_access_key_id'     => 'AKIAIOSFODNN7EXAMPLE',
                    'aws_secret_access_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
                    'aws_region'            => 'us-east-1',
                    'aws_account_id'        => '123456789012',
                ],
            ],

            // ─── GCP ──────────────────────────────────────────────────────────
            [
                'provider_slug' => 'gcp',
                'name'          => 'GCP Demo Credentials',
                'slug'          => 'gcp-demo',
                'description'   => 'Dummy GCP credentials for development/demo purposes.',
                'is_active'     => true,
                'values'        => [
                    'service_account_json' => json_encode([
                        'type'                        => 'service_account',
                        'project_id'                  => 'demo-project-123456',
                        'private_key_id'              => 'abc123def456',
                        'private_key'                 => '-----BEGIN RSA PRIVATE KEY-----\nMIIEowIBAAKCAQEA...DUMMY_KEY...\n-----END RSA PRIVATE KEY-----\n',
                        'client_email'                => 'demo-sa@demo-project-123456.iam.gserviceaccount.com',
                        'client_id'                   => '112345678901234567890',
                        'auth_uri'                    => 'https://accounts.google.com/o/oauth2/auth',
                        'token_uri'                   => 'https://oauth2.googleapis.com/token',
                        'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
                        'client_x509_cert_url'        => 'https://www.googleapis.com/robot/v1/metadata/x509/demo-sa%40demo-project-123456.iam.gserviceaccount.com',
                    ]),
                    'gcp_project_id' => 'demo-project-123456',
                    'gcp_region'     => 'us-central1',
                ],
            ],

            // ─── Slurm / HPC ──────────────────────────────────────────────────
            [
                'provider_slug' => 'slurm',
                'name'          => 'HPC Demo Credentials',
                'slug'          => 'hpc-demo',
                'description'   => 'Dummy Slurm/HPC credentials for development/demo purposes.',
                'is_active'     => true,
                'values'        => [
                    'slurm_host'              => 'hpc-login.demo.local',
                    'slurm_username'          => 'demo_user',
                    'slurm_ssh_private_key'   => "-----BEGIN RSA PRIVATE KEY-----\nMIIEowIBAAKCAQEA...DUMMY_KEY...\n-----END RSA PRIVATE KEY-----\n",
                    'slurm_partition'         => 'general',
                    'slurm_account'           => 'demo_account',
                    'slurm_max_nodes'         => '8',
                    'slurm_default_time_limit' => '01:00:00',
                ],
            ],
        ];

        foreach ($credentials as $credentialData) {
            $provider = Provider::where('slug', $credentialData['provider_slug'])->first();

            if (! $provider) {
                continue;
            }

            $credential = ProviderCredential::firstOrCreate(
                [
                    'provider_id' => $provider->id,
                    'slug'        => $credentialData['slug'],
                ],
                [
                    'name'        => $credentialData['name'],
                    'description' => $credentialData['description'],
                    'is_active'   => $credentialData['is_active'],
                ]
            );

            foreach ($credentialData['values'] as $key => $value) {
                ProviderCredentialValue::firstOrCreate(
                    [
                        'provider_credential_id' => $credential->id,
                        'field_key'              => $key,
                    ],
                    [
                        'field_value' => $value,
                    ]
                );
            }
        }
    }
}
