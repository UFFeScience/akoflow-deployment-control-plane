# ── Meta ──────────────────────────────────────────────────────────────────────

variable "environment_id" {
  description = "AkoCloud environment ID — injected automatically to guarantee unique resource names"
  type        = string
  default     = ""
}

# ── GCP ───────────────────────────────────────────────────────────────────────

variable "project_id" {
  description = "GCP project ID"
  type        = string
}

variable "region" {
  description = "GCP region to deploy into"
  type        = string
  default     = "us-central1"
}

# ── GKE Cluster ───────────────────────────────────────────────────────────────

variable "gke_version" {
  description = "Minimum Kubernetes master version for the GKE cluster"
  type        = string
  default     = "1.32"
}

variable "node_machine_type" {
  description = "GCE machine type for GKE worker nodes"
  type        = string
  default     = "n1-standard-2"
}

variable "node_disk_size_gb" {
  description = "Disk size in GB for each GKE worker node"
  type        = number
  default     = 50
}

variable "node_count" {
  description = "Number of worker nodes (used when autoscaling is disabled)"
  type        = number
  default     = 1
}

variable "gke_enable_autoscaling" {
  description = "Enable node pool autoscaling"
  type        = bool
  default     = true
}

variable "node_min_count" {
  description = "Minimum number of nodes per zone (autoscaling)"
  type        = number
  default     = 1
}

variable "node_max_count" {
  description = "Maximum number of nodes per zone (autoscaling)"
  type        = number
  default     = 5
}

# ── AkoFlow Engine ────────────────────────────────────────────────────────────

variable "engine_machine_type" {
  description = "GCE machine type for the AkoFlow engine VM"
  type        = string
  default     = "n1-standard-2"
}

variable "engine_disk_size_gb" {
  description = "Boot disk size in GB for the AkoFlow engine VM"
  type        = number
  default     = 50
}

variable "akoflow_api_port" {
  description = "TCP port the AkoFlow API listens on (opened in the firewall rule)"
  type        = number
  default     = 8080
}

variable "akoflow_allowed_ips" {
  description = "CIDR block allowed to reach the AkoFlow API port"
  type        = string
  default     = "0.0.0.0/0"
}

variable "akoflow_env" {
  description = "AkoFlow environment name written to ~/akospace/.env as AKOFLOW_ENV"
  type        = string
  default     = "dev"
}
