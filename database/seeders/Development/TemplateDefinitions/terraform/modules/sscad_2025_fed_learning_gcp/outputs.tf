# ── DfAnalyse ───────────────────────────────────────────────────────────────

output "dfanalyse_name" {
  value = google_compute_instance.dfanalyse.name
}

output "dfanalyse_public_ip" {
  value = google_compute_instance.dfanalyse.network_interface[0].access_config[0].nat_ip
}

output "dfanalyse_private_ip" {
  value = google_compute_instance.dfanalyse.network_interface[0].network_ip
}

# ── Overseer ────────────────────────────────────────────────────────────────

output "overseer_name" {
  value = google_compute_instance.overseer.name
}

output "overseer_public_ip" {
  value = google_compute_instance.overseer.network_interface[0].access_config[0].nat_ip
}

output "overseer_private_ip" {
  value = google_compute_instance.overseer.network_interface[0].network_ip
}

# ── Server ───────────────────────────────────────────────────────────────────

output "server_name" {
  value = google_compute_instance.server.name
}

output "server_public_ip" {
  value = google_compute_instance.server.network_interface[0].access_config[0].nat_ip
}

output "server_private_ip" {
  value = google_compute_instance.server.network_interface[0].network_ip
}

# ── Sites ────────────────────────────────────────────────────────────────────

output "site_1_name" {
  value = google_compute_instance.site[0].name
}

output "site_1_public_ip" {
  value = google_compute_instance.site[0].network_interface[0].access_config[0].nat_ip
}

output "site_1_private_ip" {
  value = google_compute_instance.site[0].network_interface[0].network_ip
}

output "site_2_name" {
  value = google_compute_instance.site[1].name
}

output "site_2_public_ip" {
  value = google_compute_instance.site[1].network_interface[0].access_config[0].nat_ip
}

output "site_2_private_ip" {
  value = google_compute_instance.site[1].network_interface[0].network_ip
}

output "site_3_name" {
  value = google_compute_instance.site[2].name
}

output "site_3_public_ip" {
  value = google_compute_instance.site[2].network_interface[0].access_config[0].nat_ip
}

output "site_3_private_ip" {
  value = google_compute_instance.site[2].network_interface[0].network_ip
}

output "site_4_name" {
  value = google_compute_instance.site[3].name
}

output "site_4_public_ip" {
  value = google_compute_instance.site[3].network_interface[0].access_config[0].nat_ip
}

output "site_4_private_ip" {
  value = google_compute_instance.site[3].network_interface[0].network_ip
}

output "site_5_name" {
  value = google_compute_instance.site[4].name
}

output "site_5_public_ip" {
  value = google_compute_instance.site[4].network_interface[0].access_config[0].nat_ip
}

output "site_5_private_ip" {
  value = google_compute_instance.site[4].network_interface[0].network_ip
}

output "site_6_name" {
  value = google_compute_instance.site[5].name
}

output "site_6_public_ip" {
  value = google_compute_instance.site[5].network_interface[0].access_config[0].nat_ip
}

output "site_6_private_ip" {
  value = google_compute_instance.site[5].network_interface[0].network_ip
}

output "site_7_name" {
  value = google_compute_instance.site[6].name
}

output "site_7_public_ip" {
  value = google_compute_instance.site[6].network_interface[0].access_config[0].nat_ip
}

output "site_7_private_ip" {
  value = google_compute_instance.site[6].network_interface[0].network_ip
}

output "site_8_name" {
  value = google_compute_instance.site[7].name
}

output "site_8_public_ip" {
  value = google_compute_instance.site[7].network_interface[0].access_config[0].nat_ip
}

output "site_8_private_ip" {
  value = google_compute_instance.site[7].network_interface[0].network_ip
}

output "site_9_name" {
  value = google_compute_instance.site[8].name
}

output "site_9_public_ip" {
  value = google_compute_instance.site[8].network_interface[0].access_config[0].nat_ip
}

output "site_9_private_ip" {
  value = google_compute_instance.site[8].network_interface[0].network_ip
}

output "site_10_name" {
  value = google_compute_instance.site[9].name
}

output "site_10_public_ip" {
  value = google_compute_instance.site[9].network_interface[0].access_config[0].nat_ip
}

output "site_10_private_ip" {
  value = google_compute_instance.site[9].network_interface[0].network_ip
}