terraform {
  required_version = ">= 1.5"
  required_providers {
    google = {
      source  = "hashicorp/google"
      version = "~> 5.0"
    }
  }
}

provider "google" {
  project = var.gcp_project_id
  region  = var.gcp_region
}

locals {
  cluster_name  = "akocloud-gke-${var.experiment_id}"
  akoflow_name  = "akoflow-${var.experiment_id}"
  allowed_cidrs = [for cidr in split(",", var.akoflow_allowed_ips) : trimspace(cidr)]
}

# ── VPC Network ───────────────────────────────────────────────────────────────
resource "google_compute_network" "vpc" {
  name                    = "akocloud-vpc-${var.experiment_id}"
  auto_create_subnetworks = false
}

resource "google_compute_subnetwork" "subnet" {
  name          = "akocloud-subnet-${var.experiment_id}"
  ip_cidr_range = "10.10.0.0/16"
  region        = var.gcp_region
  network       = google_compute_network.vpc.id

  secondary_ip_range {
    range_name    = "pods"
    ip_cidr_range = "10.20.0.0/20"
  }

  secondary_ip_range {
    range_name    = "services"
    ip_cidr_range = "10.30.0.0/24"
  }
}

# ── Firewall: allow Akoflow API from allowed CIDRs ───────────────────────────
resource "google_compute_firewall" "akoflow_api" {
  name    = "akocloud-akoflow-api-${var.experiment_id}"
  network = google_compute_network.vpc.name

  allow {
    protocol = "tcp"
    ports    = [tostring(var.akoflow_api_port)]
  }

  source_ranges = local.allowed_cidrs
  target_tags   = ["akoflow"]
}

resource "google_compute_firewall" "akoflow_https" {
  count   = var.akoflow_enable_https ? 1 : 0
  name    = "akocloud-akoflow-https-${var.experiment_id}"
  network = google_compute_network.vpc.name

  allow {
    protocol = "tcp"
    ports    = ["443"]
  }

  source_ranges = ["0.0.0.0/0"]
  target_tags   = ["akoflow"]
}

# ── GKE Cluster ───────────────────────────────────────────────────────────────
resource "google_container_cluster" "primary" {
  name               = local.cluster_name
  location           = var.gcp_region
  min_master_version = var.gke_version
  network            = google_compute_network.vpc.name
  subnetwork         = google_compute_subnetwork.subnet.name

  # Use a separate node pool – remove the default one
  remove_default_node_pool = true
  initial_node_count       = 1

  ip_allocation_policy {
    cluster_secondary_range_name  = "pods"
    services_secondary_range_name = "services"
  }

  logging_service    = "logging.googleapis.com/kubernetes"
  monitoring_service = "monitoring.googleapis.com/kubernetes"
}

resource "google_container_node_pool" "gke_compute" {
  name       = "gke-compute-${var.experiment_id}"
  cluster    = google_container_cluster.primary.name
  location   = var.gcp_region
  node_count = var.gke_enable_autoscaling ? null : var.gke_node_count

  dynamic "autoscaling" {
    for_each = var.gke_enable_autoscaling ? [1] : []
    content {
      min_node_count = var.gke_min_nodes
      max_node_count = var.gke_max_nodes
    }
  }

  node_config {
    machine_type = var.gke_machine_type
    disk_size_gb = var.gke_disk_size_gb
    disk_type    = "pd-ssd"

    oauth_scopes = [
      "https://www.googleapis.com/auth/cloud-platform",
    ]

    labels = {
      managed-by    = "akocloud"
      experiment-id = var.experiment_id
    }

    metadata = {
      disable-legacy-endpoints = "true"
    }
  }
}

# ── Akoflow compute VM ────────────────────────────────────────────────────────
resource "google_compute_instance" "akoflow" {
  name         = local.akoflow_name
  machine_type = var.akoflow_machine_type
  zone         = "${var.gcp_region}-a"
  tags         = ["akoflow"]

  boot_disk {
    initialize_params {
      image = "debian-cloud/debian-12"
      size  = var.akoflow_disk_size_gb
      type  = "pd-ssd"
    }
  }

  network_interface {
    network    = google_compute_network.vpc.name
    subnetwork = google_compute_subnetwork.subnet.name

    dynamic "access_config" {
      for_each = var.akoflow_enable_public_ip ? [1] : []
      content {}
    }
  }

  metadata_startup_script = <<-EOF
    #!/bin/bash
    apt-get update -y
    apt-get install -y docker.io curl
    systemctl enable docker
    systemctl start docker
    # Bootstrap Akoflow
    ${var.akoflow_bootstrap_command}
  EOF

  labels = {
    managed-by     = "akocloud"
    experiment-id  = var.experiment_id
    akoflow-version = replace(var.akoflow_version, ".", "-")
  }
}
