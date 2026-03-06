variable "aws_region" {
  type        = string
  description = "AWS region where resources will be provisioned"
  default     = "us-east-1"
}

variable "vpc_cidr" {
  type        = string
  description = "CIDR block for the VPC"
  default     = "10.0.0.0/16"
}

variable "nvflare_version" {
  type        = string
  description = "NVFlare version tag"
  default     = "2.4.0"
}

variable "fl_rounds" {
  type        = number
  description = "Number of federated learning rounds"
  default     = 100
}

variable "server_port" {
  type    = number
  default = 8002
}

variable "admin_port" {
  type    = number
  default = 8003
}

variable "overseer_port" {
  type    = number
  default = 8443
}

# ── nvflare-server instance ──────────────────────────────────────────────────
variable "server_instance_type" {
  type    = string
  default = "c5.2xlarge"
}

variable "server_disk_size_gb" {
  type    = number
  default = 50
}

variable "server_docker_image" {
  type    = string
  default = "nvflare/nvflare"
}

variable "server_docker_command" {
  type    = string
  default = "docker run nvflare"
}

variable "server_workspace_dir" {
  type    = string
  default = "/opt/nvflare/workspace/server"
}

# ── nvflare-overseer instance ────────────────────────────────────────────────
variable "overseer_instance_type" {
  type    = string
  default = "t3.medium"
}

variable "overseer_disk_size_gb" {
  type    = number
  default = 20
}

variable "overseer_docker_image" {
  type    = string
  default = "nvflare/nvflare"
}

variable "overseer_docker_command" {
  type    = string
  default = "docker run nvflare"
}

variable "overseer_workspace_dir" {
  type    = string
  default = "/opt/nvflare/workspace/overseer"
}

# ── nvflare-dfanalyse instance ────────────────────────────────────────────────
variable "dfanalyse_instance_type" {
  type    = string
  default = "r5.xlarge"
}

variable "dfanalyse_disk_size_gb" {
  type    = number
  default = 100
}

variable "dfanalyse_docker_image" {
  type    = string
  default = "nvflare/dfanalyse"
}

variable "dfanalyse_docker_command" {
  type    = string
  default = "docker run dfAnalyse"
}

variable "dfanalyse_workspace_dir" {
  type    = string
  default = "/opt/nvflare/workspace/dfanalyse"
}

variable "dfanalyse_output_bucket" {
  type    = string
  default = ""
}

# ── nvflare-site instance ─────────────────────────────────────────────────────
variable "site_instance_type" {
  type    = string
  default = "c5.xlarge"
}

variable "site_disk_size_gb" {
  type    = number
  default = 80
}

variable "site_docker_image" {
  type    = string
  default = "nvflare/nvflare"
}

variable "site_docker_command" {
  type    = string
  default = "docker run nvflare"
}

variable "site_workspace_dir" {
  type    = string
  default = "/opt/nvflare/workspace/site"
}

variable "site_local_dataset_path" {
  type    = string
  default = "/data/local"
}

variable "key_name" {
  type        = string
  description = "EC2 key pair name for SSH access"
  default     = ""
}

variable "experiment_id" {
  type        = string
  description = "AkôCloud experiment ID (used as tag)"
  default     = ""
}
