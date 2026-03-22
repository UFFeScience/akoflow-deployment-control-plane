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

# ── NGINX Configuration ──────────────────────────────────────────────────────

variable "nginx_port" {
  description = "Host port NGINX is exposed on. Opened in the GCP firewall rule."
  type        = number
  default     = 80
}

variable "nginx_server_name" {
  description = "Value of the nginx server_name directive. Use _ to catch all hostnames."
  type        = string
  default     = "_"
}

variable "nginx_worker_processes" {
  description = "Number of NGINX worker processes. \"auto\" maps to available CPUs."
  type        = string
  default     = "auto"
}

variable "nginx_worker_connections" {
  description = "Maximum simultaneous connections per worker process."
  type        = number
  default     = 1024
}

variable "nginx_keepalive_timeout" {
  description = "Keep-alive timeout in seconds."
  type        = number
  default     = 65
}

variable "nginx_client_max_body_size" {
  description = "Maximum allowed client request body size (e.g. 1m, 10m)."
  type        = string
  default     = "1m"
}

variable "nginx_gzip" {
  description = "Enable or disable gzip compression (on | off)."
  type        = string
  default     = "on"
}

# ── HTML Page ─────────────────────────────────────────────────────────────────

variable "html_page_title" {
  description = "Title tag and main heading text for the default index.html page."
  type        = string
  default     = "Hello from NGINX"
}

variable "html_page_body" {
  description = "Paragraph text displayed below the heading on the index page."
  type        = string
  default     = "Your MicroNGINX instance is running successfully."
}
