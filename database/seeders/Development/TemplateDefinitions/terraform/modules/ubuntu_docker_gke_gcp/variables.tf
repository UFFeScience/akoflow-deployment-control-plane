# ── Meta ──────────────────────────────────────────────────────────────────────

variable "environment_id" {
  description = "AkoCloud environment ID — injected automatically to guarantee unique resource names"
  type        = string
  default     = ""
}

# ── GCP Project / Region ──────────────────────────────────────────────────────

variable "project_id" {
  description = "GCP project ID"
  type        = string
}

variable "region" {
  description = "GCP region to deploy all resources into"
  type        = string
  default     = "us-central1"
}

variable "zone" {
  description = "GCP zone for the Ubuntu Docker VM (optional — defaults to <region>-a)"
  type        = string
  default     = ""
}

# ── Network ───────────────────────────────────────────────────────────────────

variable "subnet_cidr" {
  description = "Primary CIDR block for the subnet"
  type        = string
  default     = "10.0.0.0/24"
}

variable "pods_cidr" {
  description = "Secondary CIDR range used for GKE pods (alias IP)"
  type        = string
  default     = "10.48.0.0/14"
}

variable "services_cidr" {
  description = "Secondary CIDR range used for GKE services (alias IP)"
  type        = string
  default     = "10.52.0.0/20"
}

# ── Ubuntu Docker VM ──────────────────────────────────────────────────────────

variable "instance_machine_type" {
  description = "Compute Engine machine type for the Ubuntu Docker VM"
  type        = string
  default     = "e2-medium"
}

variable "ssh_public_key" {
  description = "SSH public key in \"user:ssh-rsa AAAA...\" format added to instance metadata. Leave empty to disable SSH."
  type        = string
  default     = ""
}

# ── GKE Cluster ───────────────────────────────────────────────────────────────

variable "cluster_name" {
  description = "Name suffix for the GKE cluster — final name is prefixed with the environment ID"
  type        = string
  default     = "gke"
}

variable "kubernetes_version" {
  description = "Minimum Kubernetes master version for the GKE cluster"
  type        = string
  default     = "1.31"
}

# ── GKE Node Pool ─────────────────────────────────────────────────────────────

variable "node_machine_type" {
  description = "Compute Engine machine type for GKE worker nodes"
  type        = string
  default     = "e2-medium"
}

variable "desired_node_count" {
  description = "Initial / desired number of nodes per zone in the node pool"
  type        = number
  default     = 2
}

variable "min_node_count" {
  description = "Minimum number of nodes per zone for the autoscaler"
  type        = number
  default     = 1
}

variable "max_node_count" {
  description = "Maximum number of nodes per zone for the autoscaler"
  type        = number
  default     = 3
}
