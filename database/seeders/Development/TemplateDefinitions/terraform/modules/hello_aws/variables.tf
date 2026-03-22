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

# ── NGINX Configuration ──────────────────────────────────────────────────────

variable "nginx_port" {
  description = "Host port NGINX is exposed on. Opened in the security group ingress rule."
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
