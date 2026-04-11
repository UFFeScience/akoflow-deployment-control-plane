# ── Meta ──────────────────────────────────────────────────────────────────────

variable "environment_id" {
  description = "AkoCloud environment ID used to guarantee unique resource names"
  type        = string
  default     = ""
}

# ── GCP Cloud ────────────────────────────────────────────────────────────────

variable "project_id" {
  description = "GCP project ID"
  type        = string
}

variable "region" {
  description = "GCP region"
  type        = string
  default     = "us-east1"
}

variable "zone" {
  description = "GCP zone"
  type        = string
  default     = "us-east1-b"
}

variable "network_name" {
  description = "GCP VPC network name"
  type        = string
  default     = "default"
}

variable "subnet_name" {
  description = "GCP subnet name"
  type        = string
  default     = "default"
}

variable "image_id" {
  description = "Boot image self-link used by all instances"
  type        = string
  default     = "projects/ubuntu-os-cloud/global/images/family/ubuntu-2204-lts"
}

variable "ssh_public_key" {
  description = "SSH public key (raw key, without the user prefix)"
  type        = string
  default     = ""
}

variable "ssh_user" {
  description = "OS user that will own the injected SSH public key (e.g. ubuntu)"
  type        = string
  default     = "ubuntu"
}

# ── Experiment Metadata ──────────────────────────────────────────────────────

variable "experiment_name" {
  description = "Experiment identifier used to prefix the resources"
  type        = string
  default     = "ccpe-2026-c1"
}

variable "description" {
  description = "Human-readable experiment description"
  type        = string
  default     = "Scenario 1: n2-highmem-16 Server + n2-standard-16 Clients"
}

variable "algorithm" {
  description = "Execution algorithm"
  type        = string
  default     = "dbscan"
}

variable "clients" {
  description = "Number of clients/sites in the experiment"
  type        = number
  default     = 10
}

variable "dataset_folder_key" {
  description = "GCS folder key with the per-site dataset files"
  type        = string
  default     = "https://storage.googleapis.com/outliers-ccpe-2026/dataset/sample_desdr2"
}

variable "site_folder_url" {
  description = "GCS ZIP URL containing the NVFlare workspace used by the sites"
  type        = string
  default     = "https://storage.googleapis.com/outliers-ccpe-2026/infra-sscad-2/prod_01.zip"
}

# ── Instance Types ──────────────────────────────────────────────────────────

variable "dfanalyse_machine_type" {
  description = "Machine type for the DfAnalyse node"
  type        = string
  default     = "e2-standard-4"
}

variable "overseer_machine_type" {
  description = "Machine type for the Overseer node"
  type        = string
  default     = "n2-highmem-16"
}

variable "server_machine_type" {
  description = "Machine type for the Server node"
  type        = string
  default     = "n2-highmem-16"
}

variable "site_machine_type" {
  description = "Machine type for the site fleet"
  type        = string
  default     = "n2-standard-16"
}