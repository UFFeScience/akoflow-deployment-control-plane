variable "environment_id" {
  description = "AkoCloud environment ID — injected automatically to guarantee unique resource names"
  type        = string
  default     = ""
}

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

variable "instance_type" {
  description = "EC2 instance type"
  type        = string
  default     = "t3.micro"
}

variable "ami_id" {
  description = "Explicit AMI ID — if empty the latest Ubuntu 22.04 AMI is used"
  type        = string
  default     = ""
}

variable "key_name" {
  description = "Name of the AWS Key Pair for SSH access (required for Ansible to connect)"
  type        = string
  default     = ""
}

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
