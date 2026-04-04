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
        $awsTemplate = <<<'HCL'
terraform {
  required_version = ">= 1.5"
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
  }
}

# Credentials injected via env: AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, AWS_DEFAULT_REGION
provider "aws" {}

# Calls AWS STS to verify the credentials have valid API access
data "aws_caller_identity" "current" {}

output "account_id" {
  value = data.aws_caller_identity.current.account_id
}

output "user_id" {
  value = data.aws_caller_identity.current.user_id
}
HCL;

        $gcpTemplate = <<<'HCL'
terraform {
  required_version = ">= 1.5"
  required_providers {
    google = {
      source  = "hashicorp/google"
      version = "~> 5.0"
    }
  }
}

# Credentials injected via env: GOOGLE_CREDENTIALS, GOOGLE_PROJECT, GOOGLE_REGION
provider "google" {}

# Calls Resource Manager API (projects.get) — requires resourcemanager.projects.get on the project
data "google_project" "current" {}

output "project_id" {
  value = data.google_project.current.project_id
}

output "project_number" {
  value = data.google_project.current.number
}
HCL;

        $hpcTemplate = <<<'HCL'
terraform {
  required_version = ">= 1.5"
  required_providers {
    null = {
      source  = "hashicorp/null"
      version = "~> 3.0"
    }
  }
}

# SSH host and credentials injected via tfvars / env
# Adjust this template to match your HPC cluster connectivity check
resource "null_resource" "health_check" {
  connection {
    type        = "ssh"
    host        = var.slurm_host
    user        = var.slurm_username
    private_key = var.slurm_ssh_private_key
  }

  provisioner "remote-exec" {
    inline = ["sinfo --version"]
  }
}

variable "slurm_host"            {}
variable "slurm_username"        {}
variable "slurm_ssh_private_key" { sensitive = true }
HCL;

        $localTemplate = <<<'HCL'
terraform {
  required_version = ">= 1.5"
  required_providers {
    null = {
      source  = "hashicorp/null"
      version = "~> 3.0"
    }
  }
}

variable "host" {}
variable "user" {}

variable "ssh_password" {
  default   = ""
  sensitive = true
}

variable "ssh_private_key" {
  default   = ""
  sensitive = true
}

# Connects via SSH (password or private key) and runs basic commands to verify the host is reachable
resource "null_resource" "health_check" {
  provisioner "remote-exec" {
    connection {
      type        = "ssh"
      host        = var.host
      user        = var.user
      password    = var.ssh_password != "" ? var.ssh_password : null
      private_key = var.ssh_private_key != "" ? var.ssh_private_key : null
    }

    inline = [
      "echo 'Connected to host'",
      "hostname",
      "docker ps",
      "kubectl get pods || true",
    ]
  }
}
HCL;

        $credentials = [
            // ─── AWS ──────────────────────────────────────────────────────────
            [
                'provider_slug'         => 'aws',
                'name'                  => 'AWS Demo Credentials',
                'slug'                  => 'aws-demo',
                'description'           => 'Dummy AWS credentials for development/demo purposes.',
                'is_active'             => true,
                'health_check_template' => $awsTemplate,
                'values'                => [
                    'aws_access_key_id'     => 'ads',
                    'aws_secret_access_key' => 'sda',
                    'aws_region'            => 'us-east-1',
                    'aws_account_id'        => '407772390783',
                    // Private key content for Ansible SSH access.
                    // In production this is the PEM file for the key_name configured in the template.
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
                'health_check_template' => $gcpTemplate,
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
                'health_check_template' => $hpcTemplate,
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
                'health_check_template' => $localTemplate,
                'values'                => [
                    'host'            => 'host.docker.internal',
                    'user'            => 'ovvesley',
                    'ssh_password'    => '1334',
                    'ssh_private_key' => '',
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
                    'name'                  => $credentialData['name'],
                    'description'           => $credentialData['description'],
                    'is_active'             => $credentialData['is_active'],
                    'health_check_template' => $credentialData['health_check_template'] ?? null,
                ]
            );

            // Always keep template up-to-date on subsequent seed runs
            if ($credential->health_check_template !== ($credentialData['health_check_template'] ?? null)) {
                $credential->update(['health_check_template' => $credentialData['health_check_template'] ?? null]);
            }

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
