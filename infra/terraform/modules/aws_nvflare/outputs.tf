output "vpc_id" {
  description = "VPC ID"
  value       = aws_vpc.main.id
}

output "server_public_ip" {
  description = "NVFlare server public IP"
  value       = aws_instance.server.public_ip
}

output "overseer_public_ip" {
  description = "NVFlare overseer public IP"
  value       = aws_instance.overseer.public_ip
}

output "dfanalyse_public_ip" {
  description = "NVFlare dfAnalyse public IP"
  value       = aws_instance.dfanalyse.public_ip
}

output "site_public_ip" {
  description = "NVFlare site public IP"
  value       = aws_instance.site.public_ip
}

output "security_group_id" {
  description = "NVFlare security group ID"
  value       = aws_security_group.nvflare.id
}
