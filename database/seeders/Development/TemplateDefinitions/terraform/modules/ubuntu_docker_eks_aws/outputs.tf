# ── Ubuntu Docker VM ──────────────────────────────────────────────────────────

output "docker_vm_instance_id" {
  description = "EC2 instance ID for the Ubuntu Docker VM"
  value       = aws_instance.docker_vm.id
}

output "public_ip" {
  description = "Public IP address of the Ubuntu Docker VM"
  value       = aws_instance.docker_vm.public_ip
}

output "private_ip" {
  description = "Private IP address of the Ubuntu Docker VM"
  value       = aws_instance.docker_vm.private_ip
}

output "docker_vm_security_group_id" {
  description = "Security group ID attached to the Ubuntu Docker VM"
  value       = aws_security_group.docker_vm.id
}

# ── EKS Cluster ───────────────────────────────────────────────────────────────

output "eks_cluster_name" {
  description = "EKS cluster name"
  value       = aws_eks_cluster.main.name
}

output "eks_cluster_endpoint" {
  description = "EKS API server endpoint"
  value       = aws_eks_cluster.main.endpoint
}

output "eks_cluster_arn" {
  description = "EKS cluster ARN"
  value       = aws_eks_cluster.main.arn
}

output "eks_cluster_ca_data" {
  description = "Base64-encoded certificate authority data for the EKS cluster"
  value       = aws_eks_cluster.main.certificate_authority[0].data
  sensitive   = true
}

output "eks_kubernetes_version" {
  description = "Kubernetes version running on the cluster"
  value       = aws_eks_cluster.main.version
}

# ── Network ───────────────────────────────────────────────────────────────────

output "vpc_id" {
  description = "ID of the VPC created for this environment"
  value       = aws_vpc.main.id
}

output "subnet_public_1_id" {
  description = "ID of the first public subnet"
  value       = aws_subnet.public_1.id
}

output "subnet_public_2_id" {
  description = "ID of the second public subnet"
  value       = aws_subnet.public_2.id
}

# ── AkoCloud ──────────────────────────────────────────────────────────────────

output "akoflow_iframe_url" {
  description = "URL exposed as iframe inside AkoCloud (Docker VM port 8080)"
  value       = "http://${aws_instance.docker_vm.public_ip}:8080"
}
