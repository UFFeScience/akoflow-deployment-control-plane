output "host" {
  description = "Target host address"
  value       = var.host
  depends_on  = [null_resource.verify_host]
}

output "akoflow_url" {
  description = "URL to access the AkôFlow workflow engine after installation"
  value       = "http://localhost:${var.akoflow_workflow_engine_host_port}"
  depends_on  = [null_resource.verify_host]
}
