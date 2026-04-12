variable "host" {
  description = "SSH host / IP address of the target machine"
}

variable "user" {
  description = "SSH username"
}

variable "ssh_password" {
  description = "SSH password (leave empty to use private key)"
  default     = ""
  sensitive   = true
}

variable "ssh_private_key" {
  description = "SSH private key PEM content (leave empty to use password)"
  default     = ""
  sensitive   = true
}

variable "akoflow_workflow_engine_host_port" {
  description = "Port to expose the AkôFlow workflow engine on the host"
  default     = "18080"
}

variable "environment_id" {
  description = "AkoCloud environment ID"
  default     = ""
}
