# ── Meta ──────────────────────────────────────────────────────────────────────

variable "environment_id" {
  description = "AkoCloud environment ID — injected automatically to guarantee unique resource names"
  type        = string
  default     = ""
}

# ── Cloud / Region ────────────────────────────────────────────────────────────

variable "region" {
  description = "AWS region to deploy into"
  type        = string
  default     = "us-east-1"
}

variable "zone" {
  description = "Availability zone within the region (optional — defaults to <region>a)"
  type        = string
  default     = ""
}

# ── Instance ──────────────────────────────────────────────────────────────────

variable "instance_type" {
  description = "EC2 instance type"
  type        = string
  default     = "t3.micro"
}

variable "ami_id" {
  description = "Explicit AMI ID — if empty the latest AMI matching ami_filter/ami_owners is used"
  type        = string
  default     = ""
}

variable "ami_filter" {
  description = "Name-pattern filter used by the aws_ami data source when ami_id is empty"
  type        = string
  default     = "amzn2-ami-hvm-*-x86_64-gp2"
}

variable "ami_owners" {
  description = "Owner alias or account ID for the aws_ami data source"
  type        = string
  default     = "amazon"
}

# ── Network ───────────────────────────────────────────────────────────────────

variable "vpc_id" {
  description = "VPC ID for the security group (optional — uses the default VPC when empty)"
  type        = string
  default     = ""
}

variable "subnet_id" {
  description = "Subnet ID for the instance (optional — AWS selects a subnet automatically when empty)"
  type        = string
  default     = ""
}

# ── NGINX ─────────────────────────────────────────────────────────────────────

variable "nginx_port" {
  description = "Host port NGINX is exposed on. Opened in the security group ingress rule."
  type        = number
  default     = 80
}
