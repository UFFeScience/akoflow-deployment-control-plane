terraform {
  required_providers {
    google = {
      source  = "hashicorp/google"
      version = ">= 5.0"
    }
  }
}

provider "google" {
  project = var.project_id
  region  = var.region
  zone    = var.zone
}

locals {
  experiment_slug = lower(replace(var.experiment_name, " ", "-"))
  resource_prefix = var.environment_id != "" ? "akocloud-${var.environment_id}-${local.experiment_slug}" : local.experiment_slug
  firewall_tag    = "${local.resource_prefix}-sscad"

  bootstrap_startup_script = <<-EOF
    #!/bin/bash
    set -eux

    export DEBIAN_FRONTEND=noninteractive
    apt-get update -y
    apt-get install -y \
      ca-certificates \
      curl \
      docker.io \
      gnupg \
      lsb-release \
      python3 \
      python3-pip \
      git \
      jq \
      ansible

    systemctl enable docker
    systemctl start docker

    usermod -aG docker ubuntu 2>/dev/null || true

    mkdir -p /opt/sscad
    cat > /opt/sscad/bootstrap.json <<'JSONEOF'
    {
      "experiment_name": "${var.experiment_name}",
      "description": "${var.description}",
      "algorithm": "${var.algorithm}",
      "clients": ${var.clients},
      "dataset_folder_key": "${var.dataset_folder_key}",
      "site_folder_url": "${var.site_folder_url}",
      "bootstrap_user": "${var.ssh_user}"
    }
    JSONEOF
    chmod 0644 /opt/sscad/bootstrap.json
  EOF
}

resource "google_compute_firewall" "internal" {
  name    = "${local.resource_prefix}-internal"
  network = "global/networks/${var.network_name}"

  allow {
    protocol = "all"
  }

  direction     = "INGRESS"
  source_tags   = [local.firewall_tag]
  target_tags   = [local.firewall_tag]
}

resource "google_compute_firewall" "external" {
  name    = "${local.resource_prefix}-external"
  network = "global/networks/${var.network_name}"

  allow {
    protocol = "tcp"
    ports    = ["22", "80", "8080", "8443", "8002", "8003", "22000"]
  }

  direction     = "INGRESS"
  source_ranges = ["0.0.0.0/0"]
  target_tags   = [local.firewall_tag]
}

resource "google_compute_instance" "dfanalyse" {
  name         = "${local.resource_prefix}-dfanalyse"
  machine_type = var.dfanalyse_machine_type
  zone         = var.zone

  boot_disk {
    initialize_params {
      image = var.image_id
      size  = 32
    }
  }

  network_interface {
    subnetwork = "projects/${var.project_id}/regions/${var.region}/subnetworks/${var.subnet_name}"
    access_config {}
  }

  metadata = var.ssh_public_key != "" ? { "ssh-keys" = "${var.ssh_user}:${var.ssh_public_key}" } : {}
  metadata_startup_script = local.bootstrap_startup_script

  tags = [local.firewall_tag]

  labels = {
    environment = local.experiment_slug
    role        = "dfanalyse"
  }
}

resource "google_compute_instance" "overseer" {
  name         = "${local.resource_prefix}-overseer"
  machine_type = var.overseer_machine_type
  zone         = var.zone

  boot_disk {
    initialize_params {
      image = var.image_id
      size  = 32
    }
  }

  network_interface {
    subnetwork = "projects/${var.project_id}/regions/${var.region}/subnetworks/${var.subnet_name}"
    access_config {}
  }

  metadata = var.ssh_public_key != "" ? { "ssh-keys" = "${var.ssh_user}:${var.ssh_public_key}" } : {}
  metadata_startup_script = local.bootstrap_startup_script

  tags = [local.firewall_tag]

  labels = {
    environment = local.experiment_slug
    role        = "overseer"
  }
}

resource "google_compute_instance" "server" {
  name         = "${local.resource_prefix}-server"
  machine_type = var.server_machine_type
  zone         = var.zone

  boot_disk {
    initialize_params {
      image = var.image_id
      size  = 32
    }
  }

  network_interface {
    subnetwork = "projects/${var.project_id}/regions/${var.region}/subnetworks/${var.subnet_name}"
    access_config {}
  }

  metadata = var.ssh_public_key != "" ? { "ssh-keys" = "${var.ssh_user}:${var.ssh_public_key}" } : {}
  metadata_startup_script = local.bootstrap_startup_script

  tags = [local.firewall_tag]

  labels = {
    environment = local.experiment_slug
    role        = "server"
  }
}

resource "google_compute_instance" "site" {
  count        = 10
  name         = "${local.resource_prefix}-site-${count.index + 1}"
  machine_type = var.site_machine_type
  zone         = var.zone

  boot_disk {
    initialize_params {
      image = var.image_id
      size  = 32
    }
  }

  network_interface {
    subnetwork = "projects/${var.project_id}/regions/${var.region}/subnetworks/${var.subnet_name}"
    access_config {}
  }

  metadata = var.ssh_public_key != "" ? { "ssh-keys" = "${var.ssh_user}:${var.ssh_public_key}" } : {}
  metadata_startup_script = local.bootstrap_startup_script

  tags = [local.firewall_tag]

  labels = {
    environment = local.experiment_slug
    role        = "site"
  }
}