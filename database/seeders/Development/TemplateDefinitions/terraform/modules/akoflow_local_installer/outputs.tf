output "host" {
  description = "Target host address"
  value       = var.host
  depends_on  = [null_resource.verify_host]
}

output "akoflow_url" {
  description = "URL to access AkôFlow after installation"
  value       = "http://localhost:${var.akoflow_port}"
  depends_on  = [null_resource.verify_host]
}
