<?php

namespace Database\Seeders\Development\ProviderCredentials;

class HpcTemplate
{
    public static function get(): string
    {
        return <<<'HCL'
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
    }
}
