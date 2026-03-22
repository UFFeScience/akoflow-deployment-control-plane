variable "environment_id" {
  description = "AkoCloud environment ID — injected automatically, used to guarantee unique resource names"
  type        = string
  default     = ""
}

variable "provider" {
  description = "Cloud provider identifier"
  type        = string
  default     = "gcp"
}

variable "project_id" {
  description = "GCP project ID"
  type        = string
}

variable "region" {
  description = "GCP region"
  type        = string
}

variable "zone" {
  description = "GCP zone"
  type        = string
}

variable "instance_name" {
  description = "Compute instance name"
  type        = string
  default     = "hello-docker"
}

variable "machine_type" {
  description = "Compute machine type"
  type        = string
  default     = "e2-micro"
}

variable "image" {
  description = "Boot disk image self_link (override)"
  type        = string
  default     = ""
}

variable "startup_script" {
  description = "Custom startup script to run after Docker install"
  type        = string
  default     = ""
}

variable "docker_image" {
  description = "Docker image to run"
  type        = string
  default     = "nginx:latest"
}

variable "docker_args" {
  description = "Additional arguments passed to docker run"
  type        = string
  default     = ""
}
