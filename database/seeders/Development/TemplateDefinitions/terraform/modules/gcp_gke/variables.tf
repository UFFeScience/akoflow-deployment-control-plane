variable "gcp_project_id" {
  type        = string
  description = "GCP project ID"
}

variable "gcp_region" {
  type        = string
  description = "GCP region"
  default     = "us-central1"
}

variable "gke_version" {
  type        = string
  description = "GKE master version"
  default     = "1.27"
}

variable "akoflow_bootstrap_command" {
  type        = string
  description = "Shell command to bootstrap Akoflow"
  default     = "curl -fsSL https://akoflow.com/run | bash"
}

variable "akoflow_allowed_ips" {
  type        = string
  description = "Comma-separated CIDR blocks allowed to reach Akoflow API"
  default     = "0.0.0.0/0"
}

# ── gke-compute node pool ─────────────────────────────────────────────────────
variable "gke_node_count" {
  type    = number
  default = 5
}

variable "gke_machine_type" {
  type    = string
  default = "n1-standard-4"
}

variable "gke_disk_size_gb" {
  type    = number
  default = 100
}

variable "gke_enable_autoscaling" {
  type    = bool
  default = true
}

variable "gke_min_nodes" {
  type    = number
  default = 1
}

variable "gke_max_nodes" {
  type    = number
  default = 10
}

# ── akoflow-compute ───────────────────────────────────────────────────────────
variable "akoflow_version" {
  type    = string
  default = "1.0.0"
}

variable "akoflow_deployment_mode" {
  type    = string
  default = "standard"
}

variable "akoflow_replicas" {
  type    = number
  default = 1
}

variable "akoflow_machine_type" {
  type    = string
  default = "n1-highmem-8"
}

variable "akoflow_disk_size_gb" {
  type    = number
  default = 200
}

variable "akoflow_enable_public_ip" {
  type    = bool
  default = true
}

variable "akoflow_api_port" {
  type    = number
  default = 8080
}

variable "akoflow_enable_https" {
  type    = bool
  default = true
}

variable "environment_id" {
  type        = string
  description = "AkôCloud environment ID (used as tag)"
  default     = ""
}
