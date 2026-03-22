# ── Meta ──────────────────────────────────────────────────────────────────────

variable "environment_id" {
  description = "AkoCloud environment ID — injected automatically to guarantee unique resource names"
  type        = string
  default     = ""
}

# ── Cloud / Region ────────────────────────────────────────────────────────────

variable "project_id" {
  description = "GCP project ID"
  type        = string
  default     = ""
}

variable "region" {
  description = "GCP region"
  type        = string
  default     = "us-central1"
}

variable "zone" {
  description = "GCP zone (e.g. us-central1-a)"
  type        = string
  default     = "us-central1-a"
}

# ── Instance ──────────────────────────────────────────────────────────────────

variable "instance_name" {
  description = "Base name for the compute instance"
  type        = string
  default     = "hello-docker"
}

variable "machine_type" {
  description = "GCP machine type (e.g. e2-micro, n1-standard-1)"
  type        = string
  default     = "e2-micro"
}

variable "image_gcp" {
  description = "Boot disk image self_link — if empty the data source (image_family_gcp/image_project_gcp) is used"
  type        = string
  default     = ""
}

variable "image_family_gcp" {
  description = "Image family used by the data source when image_gcp is empty"
  type        = string
  default     = "ubuntu-2204-lts"
}

variable "image_project_gcp" {
  description = "Image project used by the data source when image_gcp is empty"
  type        = string
  default     = "ubuntu-os-cloud"
}

# ── Network ───────────────────────────────────────────────────────────────────

variable "network_gcp" {
  description = "VPC network name or self_link"
  type        = string
  default     = "default"
}

# ── Security Group / Firewall ─────────────────────────────────────────────────

variable "ingress_from_port" {
  description = "Start of the ingress port range"
  type        = number
  default     = 80
}

variable "ingress_to_port" {
  description = "End of the ingress port range"
  type        = number
  default     = 80
}

variable "ingress_protocol" {
  description = "IP protocol for the ingress rule (tcp, udp, icmp)"
  type        = string
  default     = "tcp"
}

variable "ingress_cidr" {
  description = "Source CIDR block allowed for inbound traffic"
  type        = string
  default     = "0.0.0.0/0"
}

# ── Application ───────────────────────────────────────────────────────────────

variable "user_data" {
  description = "Full bash startup script executed on instance boot (metadata_startup_script)"
  type        = string
  default     = ""
}
