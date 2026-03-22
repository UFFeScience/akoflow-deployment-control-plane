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

# ── NGINX ─────────────────────────────────────────────────────────────────────

variable "nginx_port" {
  description = "Host port NGINX is exposed on. Opened in the GCP firewall rule."
  type        = number
  default     = 80
}
