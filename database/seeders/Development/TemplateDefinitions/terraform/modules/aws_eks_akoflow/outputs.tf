output "eks_cluster_name" {
  description = "EKS cluster name"
  value       = aws_eks_cluster.main.name
}

output "eks_cluster_endpoint" {
  description = "EKS control-plane endpoint URL"
  value       = aws_eks_cluster.main.endpoint
}

output "eks_cluster_version" {
  description = "Kubernetes version of the EKS cluster"
  value       = aws_eks_cluster.main.version
}

output "engine_instance_id" {
  description = "EC2 instance ID of the AkoFlow engine VM"
  value       = aws_instance.engine.id
}

output "engine_public_ip" {
  description = "Public IP address of the AkoFlow engine VM"
  value       = aws_instance.engine.public_ip
}

output "engine_private_ip" {
  description = "Private IP address of the AkoFlow engine VM"
  value       = aws_instance.engine.private_ip
}

output "akoflow_api_url" {
  description = "AkoFlow API base URL"
  value       = "http://${aws_instance.engine.public_ip}:${var.akoflow_api_port}"
}

output "akoflow_iframe_url" {
  description = "AkoFlow URL exposed as iframe inside AkoCloud"
  value       = "http://${aws_instance.engine.public_ip}:${var.akoflow_api_port}"
}

output "vpc_id" {
  description = "VPC ID"
  value       = aws_vpc.main.id
}
