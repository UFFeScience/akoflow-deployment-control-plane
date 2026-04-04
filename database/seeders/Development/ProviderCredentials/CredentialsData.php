<?php

namespace Database\Seeders\Development\ProviderCredentials;

class CredentialsData
{
    public static function get(): array
    {
        return [
            // ─── AWS ──────────────────────────────────────────────────────────
            [
                'provider_slug'         => 'aws',
                'name'                  => 'AWS Demo Credentials',
                'slug'                  => 'aws-demo',
                'description'           => 'Dummy AWS credentials for development/demo purposes.',
                'is_active'             => true,
                'health_check_template' => \Database\Seeders\Development\ProviderCredentials\AwsTemplate::get(),
                'values'                => [
                    'aws_access_key_id'     => 'ads',
                    'aws_secret_access_key' => 'sda',
                    'aws_region'            => 'us-east-1',
                    'aws_account_id'        => '407772390783',
                    'SSH_PRIVATE_KEY'       => "",
                ],
            ],

            // ─── GCP ──────────────────────────────────────────────────────────
            [
                'provider_slug'         => 'gcp',
                'name'                  => 'GCP Demo Credentials',
                'slug'                  => 'gcp-demo',
                'description'           => 'Dummy GCP credentials for development/demo purposes.',
                'is_active'             => true,
                'health_check_template' => \Database\Seeders\Development\ProviderCredentials\GcpTemplate::get(),
                'values'                => [
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
                'provider_slug'         => 'slurm',
                'name'                  => 'HPC Demo Credentials',
                'slug'                  => 'hpc-demo',
                'description'           => 'Dummy Slurm/HPC credentials for development/demo purposes.',
                'is_active'             => true,
                'health_check_template' => \Database\Seeders\Development\ProviderCredentials\HpcTemplate::get(),
                'values'                => [
                    'slurm_host'              => 'hpc-login.demo.local',
                    'slurm_username'          => 'demo_user',
                    'slurm_ssh_private_key'   => "-----BEGIN RSA PRIVATE KEY-----\nMIIEowIBAAKCAQEA...DUMMY_KEY...\n-----END RSA PRIVATE KEY-----\n",
                    'slurm_partition'         => 'general',
                    'slurm_account'           => 'demo_account',
                    'slurm_max_nodes'         => '8',
                    'slurm_default_time_limit' => '01:00:00',
                ],
            ],

            // ─── Local / On-Prem (SSH) ────────────────────────────────────────
            [
                'provider_slug'         => 'local',
                'name'                  => 'Local Host (SSH)',
                'slug'                  => 'local-ssh',
                'description'           => 'Connects to a local or on-prem machine via SSH.',
                'is_active'             => true,
                'health_check_template' => \Database\Seeders\Development\ProviderCredentials\LocalTemplate::get(),
                'values'                => [
                    'host'            => 'host.docker.internal',
                    'user'            => 'ovvesley',
                    'ssh_password'    => '1334',
                    'ssh_private_key' => '',
                ],
            ],
        ];
    }
}
