terraform {
  required_version = ">= 1.5"
  required_providers {
    null = {
      source  = "hashicorp/null"
      version = "~> 3.0"
    }
  }
}

# Verify the target host is reachable via SSH and Docker is installed.
# Uses password auth when ssh_password is set, falls back to private key.
resource "null_resource" "verify_host" {
  provisioner "remote-exec" {
    connection {
      type        = "ssh"
      host        = var.host
      user        = var.user
      password    = var.ssh_password != "" ? var.ssh_password : null
      private_key = var.ssh_private_key != "" ? var.ssh_private_key : null
    }

    inline = [
      "echo 'Host is reachable'",
      "hostname",
      "DOCKER_BIN=$(command -v docker || ls /usr/local/bin/docker /usr/bin/docker 2>/dev/null | head -1 || true); if [ -z \"$DOCKER_BIN\" ]; then echo 'ERROR: Docker is not installed on this host'; exit 1; fi; $DOCKER_BIN --version",
    ]
  }
}
