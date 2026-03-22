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

variable "instance_name" {
  description = "Name tag applied to the EC2 instance"
  type        = string
  default     = "hello-docker"
}

variable "instance_type" {
  description = "EC2 instance type"
  type        = string
  default     = "t3.micro"
}

variable "associate_public_ip" {
  description = "Whether to associate a public IP address with the instance"
  type        = bool
  default     = true
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
  description = "IP protocol for the ingress rule (tcp, udp, icmp, or -1 for all)"
  type        = string
  default     = "tcp"
}

variable "ingress_cidr" {
  description = "CIDR block allowed for inbound traffic"
  type        = string
  default     = "0.0.0.0/0"
}

variable "egress_cidr" {
  description = "CIDR block allowed for outbound traffic"
  type        = string
  default     = "0.0.0.0/0"
}

# ── Application ───────────────────────────────────────────────────────────────

variable "user_data" {
  description = "Full bash startup script executed on first boot (user-data / cloud-init)"
  type        = string
  default     = ""
}
