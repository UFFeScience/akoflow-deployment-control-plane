<?php

namespace App\Services\CloudHealthChecks;

class SlurmHealthCheckService
{
    /**
     * Build a minimal Terraform workspace that connects to the Slurm head node via SSH
     * using the null_resource remote-exec provisioner.
     *
     * SSH credentials (host, username, private key) are stored in terraform.tfvars.json
     * and referenced as Terraform variables — no system calls from PHP.
     *
     * @param  array<string, string>  $credentialValues  field_key => field_value from ProviderCredentialValue
     * @return array{main_tf: string, env: array, tfvars: array<string, string>}
     */
    public function buildWorkspace(array $credentialValues): array
    {
        $host       = $credentialValues['slurm_host'] ?? '';
        $username   = $credentialValues['slurm_username'] ?? '';
        $privateKey = $credentialValues['slurm_ssh_private_key'] ?? '';

        if (empty($host) || empty($username) || empty($privateKey)) {
            throw new \InvalidArgumentException(
                'Missing required Slurm credentials: slurm_host, slurm_username, or slurm_ssh_private_key.',
            );
        }

        $mainTf = <<<'TF'
terraform {
  required_version = ">= 1.5"
  required_providers {
    null = {
      source  = "hashicorp/null"
      version = "~> 3.0"
    }
  }
}

variable "slurm_host" {
  type      = string
  sensitive = false
}

variable "slurm_username" {
  type      = string
  sensitive = false
}

variable "slurm_ssh_private_key" {
  type      = string
  sensitive = true
}

# Opens an SSH connection to the Slurm head node and runs `sinfo --version`.
# The connection and command happen entirely inside Terraform — no PHP system calls.
resource "null_resource" "slurm_health_check" {
  connection {
    type        = "ssh"
    host        = var.slurm_host
    user        = var.slurm_username
    private_key = var.slurm_ssh_private_key
    timeout     = "10s"
  }

  provisioner "remote-exec" {
    inline = [
      "sinfo --version",
    ]
  }
}
TF;

        return [
            'main_tf' => $mainTf,
            'env'     => [],
            'tfvars'  => [
                'slurm_host'            => $host,
                'slurm_username'        => $username,
                'slurm_ssh_private_key' => $privateKey,
            ],
        ];
    }
}
