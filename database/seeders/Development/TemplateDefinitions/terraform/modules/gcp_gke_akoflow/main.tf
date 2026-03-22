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
  project = var.project_id
  region  = var.region
}

locals {
  name        = var.environment_id != "" ? "akocloud-${var.environment_id}" : "akocloud-gke"
  gke_name    = "${local.name}-cluster"
  engine_name = "${local.name}-engine"

  common_labels = {
    managed-by     = "akocloud"
    environment-id = var.environment_id
  }
}

# ── VPC ───────────────────────────────────────────────────────────────────────

resource "google_compute_network" "vpc" {
  name                    = "${local.name}-vpc"
  auto_create_subnetworks = false
}

resource "google_compute_subnetwork" "subnet" {
  name          = "${local.name}-subnet"
  ip_cidr_range = "10.10.0.0/16"
  region        = var.region
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

# ── Firewall: allow AkoFlow API traffic ───────────────────────────────────────

resource "google_compute_firewall" "akoflow_api" {
  name    = "${local.engine_name}-api"
  network = google_compute_network.vpc.name

  allow {
    protocol = "tcp"
    ports    = [tostring(var.akoflow_api_port)]
  }

  source_ranges = [var.akoflow_allowed_ips]
  target_tags   = ["akoflow-engine"]
}

# ── Service Account for AkoFlow engine VM ────────────────────────────────────

resource "google_service_account" "engine" {
  account_id   = substr("${local.engine_name}-sa", 0, 30)
  display_name = "AkoFlow Engine Service Account (${var.environment_id})"
}

# Grant container.admin so the VM can call gcloud container clusters get-credentials
resource "google_project_iam_member" "engine_gke_admin" {
  project = var.project_id
  role    = "roles/container.admin"
  member  = "serviceAccount:${google_service_account.engine.email}"
}

# ── GKE Cluster ───────────────────────────────────────────────────────────────

resource "google_container_cluster" "main" {
  name               = local.gke_name
  location           = var.region
  min_master_version = var.gke_version
  network            = google_compute_network.vpc.name
  subnetwork         = google_compute_subnetwork.subnet.name

  # Remove the default node pool; we manage our own
  remove_default_node_pool = true
  initial_node_count       = 1

  ip_allocation_policy {
    cluster_secondary_range_name  = "pods"
    services_secondary_range_name = "services"
  }

  logging_service    = "logging.googleapis.com/kubernetes"
  monitoring_service = "monitoring.googleapis.com/kubernetes"

  deletion_protection = false
}

resource "google_container_node_pool" "workers" {
  name     = "${local.gke_name}-workers"
  cluster  = google_container_cluster.main.name
  location = var.region

  node_count = var.gke_enable_autoscaling ? null : var.node_count

  dynamic "autoscaling" {
    for_each = var.gke_enable_autoscaling ? [1] : []
    content {
      min_node_count = var.node_min_count
      max_node_count = var.node_max_count
    }
  }

  node_config {
    machine_type = var.node_machine_type
    disk_size_gb = var.node_disk_size_gb
    disk_type    = "pd-ssd"

    oauth_scopes = [
      "https://www.googleapis.com/auth/cloud-platform",
    ]

    labels = local.common_labels

    metadata = {
      disable-legacy-endpoints = "true"
    }
  }
}

# ── AkoFlow Engine Compute Instance ──────────────────────────────────────────
# Order: GKE cluster → node pool → IAM binding → engine VM.
# The startup script installs Docker, bootstraps AkoFlow, installs kubectl +
# gcloud SDK, authenticates to GKE, and creates a long-lived service-account
# token that AkoFlow uses to communicate with the cluster.

resource "google_compute_instance" "engine" {
  name         = local.engine_name
  machine_type = var.engine_machine_type
  zone         = "${var.region}-a"
  tags         = ["akoflow-engine"]

  boot_disk {
    initialize_params {
      image = "debian-cloud/debian-12"
      size  = var.engine_disk_size_gb
      type  = "pd-ssd"
    }
  }

  network_interface {
    network    = google_compute_network.vpc.name
    subnetwork = google_compute_subnetwork.subnet.name

    # Assign an ephemeral public IP so the engine is reachable
    access_config {}
  }

  service_account {
    email  = google_service_account.engine.email
    scopes = ["https://www.googleapis.com/auth/cloud-platform"]
  }

  metadata_startup_script = <<-EOF
    #!/bin/bash
    set -eux

    # ── Install Docker ────────────────────────────────────────────────────────
    apt-get update -y
    apt-get install -y docker.io curl apt-transport-https gnupg lsb-release
    systemctl enable docker
    systemctl start docker

    # ── Bootstrap AkoFlow Engine ──────────────────────────────────────────────
    curl -fsSL https://akoflow.com/run | bash

    # ── Install kubectl ───────────────────────────────────────────────────────
    KUBECTL_VERSION=$(curl -L -s https://dl.k8s.io/release/stable.txt)
    curl -LO "https://dl.k8s.io/release/$${KUBECTL_VERSION}/bin/linux/amd64/kubectl"
    chmod +x kubectl
    mv kubectl /usr/local/bin/kubectl

    # ── Install gcloud SDK + GKE auth plugin ─────────────────────────────────
    echo "deb [signed-by=/usr/share/keyrings/cloud.google.gpg] \
      https://packages.cloud.google.com/apt cloud-sdk main" \
      | tee /etc/apt/sources.list.d/google-cloud-sdk.list
    curl -fsSL https://packages.cloud.google.com/apt/doc/apt-key.gpg \
      | gpg --dearmor -o /usr/share/keyrings/cloud.google.gpg
    apt-get update -y
    apt-get install -y google-cloud-cli google-cloud-cli-gke-gcloud-auth-plugin

    # ── Authenticate and configure kubectl for GKE ────────────────────────────
    export USE_GKE_GCLOUD_AUTH_PLUGIN=True
    gcloud container clusters get-credentials ${google_container_cluster.main.name} \
      --region ${var.region} \
      --project ${var.project_id}

    # ── Create service account and generate token ─────────────────────────────
    mkdir -p /etc/akoflow
    kubectl create serviceaccount akoflow-sa --namespace default 2>/dev/null || true
    kubectl create clusterrolebinding akoflow-sa-binding \
      --clusterrole=cluster-admin \
      --serviceaccount=default:akoflow-sa 2>/dev/null || true

    # Generate a long-lived token (8760 h ≈ 1 year)
    kubectl create token akoflow-sa --namespace default --duration=8760h \
      > /etc/akoflow/k8s-token

    echo "AkoFlow Engine ready. Cluster endpoint: ${google_container_cluster.main.endpoint}"
  EOF

  labels = local.common_labels

  depends_on = [
    google_container_cluster.main,
    google_container_node_pool.workers,
    google_project_iam_member.engine_gke_admin,
  ]
}
