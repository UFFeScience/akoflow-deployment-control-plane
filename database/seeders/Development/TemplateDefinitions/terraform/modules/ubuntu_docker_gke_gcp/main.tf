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
}

locals {
  resource_prefix   = var.environment_id != "" ? "akocloud-${var.environment_id}" : "akocloud"
  cluster_full_name = "${local.resource_prefix}-${var.cluster_name}"

  docker_install_script = <<-EOF
    #!/bin/bash
    set -eux

    # Update system
    export DEBIAN_FRONTEND=noninteractive
    apt-get update -y
    apt-get install -y \
      ca-certificates \
      curl \
      gnupg \
      lsb-release

    # Add Docker GPG key
    install -m 0755 -d /etc/apt/keyrings
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg
    chmod a+r /etc/apt/keyrings/docker.gpg

    # Add Docker repository
    echo \
      "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
      $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
      tee /etc/apt/sources.list.d/docker.list > /dev/null

    # Install Docker CE
    apt-get update -y
    apt-get install -y \
      docker-ce \
      docker-ce-cli \
      containerd.io \
      docker-buildx-plugin \
      docker-compose-plugin

    # Enable and start Docker
    systemctl enable docker
    systemctl start docker

    # Allow default user to run Docker without sudo
    usermod -aG docker ubuntu 2>/dev/null || true

    echo "Docker installation complete"
  EOF
}

# ── VPC Network ───────────────────────────────────────────────────────────────

resource "google_compute_network" "main" {
  name                    = "${local.resource_prefix}-network"
  auto_create_subnetworks = false
}

resource "google_compute_subnetwork" "main" {
  name                     = "${local.resource_prefix}-subnet"
  ip_cidr_range            = var.subnet_cidr
  region                   = var.region
  network                  = google_compute_network.main.id
  private_ip_google_access = true

  secondary_ip_range {
    range_name    = "pods"
    ip_cidr_range = var.pods_cidr
  }

  secondary_ip_range {
    range_name    = "services"
    ip_cidr_range = var.services_cidr
  }
}

# ── Firewall — Docker VM ──────────────────────────────────────────────────────

resource "google_compute_firewall" "docker_vm_http" {
  name    = "${local.resource_prefix}-docker-vm-http"
  network = google_compute_network.main.id

  allow {
    protocol = "tcp"
    ports    = ["80", "443", "8080"]
  }

  direction     = "INGRESS"
  priority      = 1000
  source_ranges = ["0.0.0.0/0"]
  target_tags   = ["${local.resource_prefix}-docker-vm"]
}

resource "google_compute_firewall" "docker_vm_ssh" {
  count   = var.ssh_public_key != "" ? 1 : 0
  name    = "${local.resource_prefix}-docker-vm-ssh"
  network = google_compute_network.main.id

  allow {
    protocol = "tcp"
    ports    = ["22"]
  }

  direction     = "INGRESS"
  priority      = 1000
  source_ranges = ["0.0.0.0/0"]
  target_tags   = ["${local.resource_prefix}-docker-vm"]
}

# ── Ubuntu Docker VM ──────────────────────────────────────────────────────────

resource "google_compute_instance" "docker_vm" {
  name         = "${local.resource_prefix}-docker-vm"
  machine_type = var.instance_machine_type
  zone         = var.zone != "" ? var.zone : "${var.region}-a"

  boot_disk {
    initialize_params {
      image = "ubuntu-os-cloud/ubuntu-2204-lts"
      size  = 20
    }
  }

  network_interface {
    subnetwork = google_compute_subnetwork.main.id
    access_config {}
  }

  metadata = var.ssh_public_key != "" ? { "ssh-keys" = var.ssh_public_key } : {}

  metadata_startup_script = local.docker_install_script

  tags = ["${local.resource_prefix}-docker-vm"]
}

# ── Service Account — GKE ─────────────────────────────────────────────────────

resource "google_service_account" "gke_nodes" {
  account_id   = substr("${local.resource_prefix}-gke-sa", 0, 30)
  display_name = "GKE Node Service Account — ${local.resource_prefix}"
}

resource "google_project_iam_member" "gke_nodes_log_writer" {
  project = var.project_id
  role    = "roles/logging.logWriter"
  member  = "serviceAccount:${google_service_account.gke_nodes.email}"
}

resource "google_project_iam_member" "gke_nodes_metric_writer" {
  project = var.project_id
  role    = "roles/monitoring.metricWriter"
  member  = "serviceAccount:${google_service_account.gke_nodes.email}"
}

resource "google_project_iam_member" "gke_nodes_monitoring_viewer" {
  project = var.project_id
  role    = "roles/monitoring.viewer"
  member  = "serviceAccount:${google_service_account.gke_nodes.email}"
}

resource "google_project_iam_member" "gke_nodes_artifact_reader" {
  project = var.project_id
  role    = "roles/artifactregistry.reader"
  member  = "serviceAccount:${google_service_account.gke_nodes.email}"
}

# ── GKE Cluster ───────────────────────────────────────────────────────────────

resource "google_container_cluster" "main" {
  name     = local.cluster_full_name
  location = var.region

  # Remove the default node pool after creation — we manage nodes separately
  remove_default_node_pool = true
  initial_node_count       = 1

  network    = google_compute_network.main.id
  subnetwork = google_compute_subnetwork.main.id

  # VPC-native cluster using alias IP ranges
  ip_allocation_policy {
    cluster_secondary_range_name  = "pods"
    services_secondary_range_name = "services"
  }

  min_master_version = var.kubernetes_version

  deletion_protection = false
}

# ── GKE Node Pool ─────────────────────────────────────────────────────────────

resource "google_container_node_pool" "main" {
  name       = "${local.resource_prefix}-node-pool"
  location   = var.region
  cluster    = google_container_cluster.main.name

  initial_node_count = var.desired_node_count

  autoscaling {
    min_node_count = var.min_node_count
    max_node_count = var.max_node_count
  }

  node_config {
    machine_type    = var.node_machine_type
    service_account = google_service_account.gke_nodes.email
    image_type      = "COS_CONTAINERD"

    oauth_scopes = [
      "https://www.googleapis.com/auth/cloud-platform",
    ]

    labels = {
      environment = var.environment_id
    }

    tags = ["${local.resource_prefix}-gke-node"]
  }

  management {
    auto_repair  = true
    auto_upgrade = true
  }
}
