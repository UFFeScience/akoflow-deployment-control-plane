variable "cloud_provider" {
  description = "Cloud provider identifier"
  type        = string
  default     = "aws"
}

variable "region" {
  description = "AWS region to deploy into"
  type        = string
}

variable "zone" {
  description = "AWS availability zone (optional)"
  type        = string
  default     = ""
}

variable "instance_type" {
  description = "EC2 instance type"
  type        = string
  default     = "t3.micro"
}

variable "instance_name" {
  description = "Name tag for the instance"
  type        = string
  default     = "hello-docker"
}

variable "ami_id" {
  description = "Override AMI ID (optional)"
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
