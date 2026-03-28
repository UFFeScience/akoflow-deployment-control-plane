#!/bin/bash
set -eux
exec > >(tee /var/log/akoflow-setup.log) 2>&1
echo "=== AkoFlow multicloud setup started at $$(date) ==="
export DEBIAN_FRONTEND=noninteractive

# ─────────────────────────────────────────────────────────────────────────────
# Base packages
# ─────────────────────────────────────────────────────────────────────────────
apt-get update -y
apt-get install -y curl unzip gnupg lsb-release ca-certificates apt-transport-https jq

# ─────────────────────────────────────────────────────────────────────────────
# Docker CE
# ─────────────────────────────────────────────────────────────────────────────
install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg \
  | gpg --dearmor -o /etc/apt/keyrings/docker.gpg
chmod a+r /etc/apt/keyrings/docker.gpg
echo "deb [arch=$$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] \
  https://download.docker.com/linux/ubuntu \
  $$(. /etc/os-release && echo "$$VERSION_CODENAME") stable" \
  | tee /etc/apt/sources.list.d/docker.list
apt-get update -y
apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
systemctl enable docker && systemctl start docker
usermod -aG docker ubuntu

# ─────────────────────────────────────────────────────────────────────────────
# kubectl
# ─────────────────────────────────────────────────────────────────────────────
curl -fsSL https://pkgs.k8s.io/core:/stable:/v1.31/deb/Release.key \
  | gpg --dearmor -o /etc/apt/keyrings/kubernetes-apt-keyring.gpg
echo 'deb [signed-by=/etc/apt/keyrings/kubernetes-apt-keyring.gpg] https://pkgs.k8s.io/core:/stable:/v1.31/deb/ /' \
  | tee /etc/apt/sources.list.d/kubernetes.list
apt-get update -y
apt-get install -y kubectl

# ─────────────────────────────────────────────────────────────────────────────
# AWS CLI v2
# ─────────────────────────────────────────────────────────────────────────────
curl -fsSL "https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip" -o /tmp/awscliv2.zip
cd /tmp && unzip -q awscliv2.zip && ./aws/install && rm -rf awscliv2.zip aws
cd /

# ─────────────────────────────────────────────────────────────────────────────
# gcloud CLI + GKE auth plugin
# ─────────────────────────────────────────────────────────────────────────────
echo "deb [signed-by=/usr/share/keyrings/cloud.google.gpg] https://packages.cloud.google.com/apt cloud-sdk main" \
  | tee /etc/apt/sources.list.d/google-cloud-sdk.list
curl -fsSL https://packages.cloud.google.com/apt/doc/apt-key.gpg \
  | gpg --dearmor -o /usr/share/keyrings/cloud.google.gpg
apt-get update -y
apt-get install -y google-cloud-cli google-cloud-cli-gke-gcloud-auth-plugin
export USE_GKE_GCLOUD_AUTH_PLUGIN=True

# ─────────────────────────────────────────────────────────────────────────────
# Workspace
# ─────────────────────────────────────────────────────────────────────────────
mkdir -p /home/ubuntu/akospace /home/ubuntu/.kube
chown -R ubuntu:ubuntu /home/ubuntu/akospace /home/ubuntu/.kube

# ─────────────────────────────────────────────────────────────────────────────
# GCP service account credentials (base64-encoded to avoid heredoc issues)
# ─────────────────────────────────────────────────────────────────────────────
echo "${gcp_sa_key_b64}" | base64 --decode > /home/ubuntu/akospace/gcp-sa.json
chmod 600 /home/ubuntu/akospace/gcp-sa.json
chown ubuntu:ubuntu /home/ubuntu/akospace/gcp-sa.json
export GOOGLE_APPLICATION_CREDENTIALS=/home/ubuntu/akospace/gcp-sa.json

# ─────────────────────────────────────────────────────────────────────────────
# Configure kubeconfig — EKS
# ─────────────────────────────────────────────────────────────────────────────
echo "Configuring kubeconfig for EKS cluster: ${eks_cluster_name} ..."
aws eks update-kubeconfig \
  --name "${eks_cluster_name}" \
  --region "${aws_region}" \
  --kubeconfig /home/ubuntu/.kube/config-eks

# ─────────────────────────────────────────────────────────────────────────────
# Configure kubeconfig — GKE
# ─────────────────────────────────────────────────────────────────────────────
echo "Configuring kubeconfig for GKE cluster: ${gke_cluster_name} ..."
gcloud auth activate-service-account --key-file=/home/ubuntu/akospace/gcp-sa.json
CLOUDSDK_CORE_DISABLE_PROMPTS=1 USE_GKE_GCLOUD_AUTH_PLUGIN=True \
  gcloud container clusters get-credentials "${gke_cluster_name}" \
    --region "${gcp_region}" \
    --project "${gcp_project_id}" \
    --kubeconfig /home/ubuntu/.kube/config-gke

chown -R ubuntu:ubuntu /home/ubuntu/.kube

# ─────────────────────────────────────────────────────────────────────────────
# Apply AkoFlow manifest to both clusters
# ─────────────────────────────────────────────────────────────────────────────
AKOFLOW_YAML="https://raw.githubusercontent.com/UFFeScience/akoflow/main/pkg/server/resource/akoflow-dev-dockerdesktop.yaml"

echo "Applying AkoFlow to EKS..."
for i in 1 2 3 4 5; do
  KUBECONFIG=/home/ubuntu/.kube/config-eks kubectl apply -f "$$AKOFLOW_YAML" && break
  echo "  retry $$i/5 in 30s..."; sleep 30
done

echo "Applying AkoFlow to GKE..."
for i in 1 2 3 4 5; do
  KUBECONFIG=/home/ubuntu/.kube/config-gke USE_GKE_GCLOUD_AUTH_PLUGIN=True \
    kubectl apply -f "$$AKOFLOW_YAML" && break
  echo "  retry $$i/5 in 30s..."; sleep 30
done

# ─────────────────────────────────────────────────────────────────────────────
# Wait for akoflow-server-sa service account to exist
# ─────────────────────────────────────────────────────────────────────────────
echo "Waiting for akoflow-server-sa on EKS..."
for i in $$(seq 1 30); do
  KUBECONFIG=/home/ubuntu/.kube/config-eks \
    kubectl get serviceaccount akoflow-server-sa -n akoflow 2>/dev/null \
    && break
  echo "  waiting... ($$i/30)"; sleep 10
done

echo "Waiting for akoflow-server-sa on GKE..."
for i in $$(seq 1 30); do
  KUBECONFIG=/home/ubuntu/.kube/config-gke USE_GKE_GCLOUD_AUTH_PLUGIN=True \
    kubectl get serviceaccount akoflow-server-sa -n akoflow 2>/dev/null \
    && break
  echo "  waiting... ($$i/30)"; sleep 10
done

# ─────────────────────────────────────────────────────────────────────────────
# Generate tokens (800h)
# ─────────────────────────────────────────────────────────────────────────────
echo "Generating EKS token..."
EKS_TOKEN=$$(KUBECONFIG=/home/ubuntu/.kube/config-eks \
  kubectl create token akoflow-server-sa --duration=800h --namespace=akoflow)

echo "Generating GKE token..."
GKE_TOKEN=$$(KUBECONFIG=/home/ubuntu/.kube/config-gke USE_GKE_GCLOUD_AUTH_PLUGIN=True \
  kubectl create token akoflow-server-sa --duration=800h --namespace=akoflow)

# ─────────────────────────────────────────────────────────────────────────────
# API server endpoints
# ─────────────────────────────────────────────────────────────────────────────
EKS_API=$$(KUBECONFIG=/home/ubuntu/.kube/config-eks \
  kubectl config view --minify -o jsonpath='{.clusters[0].cluster.server}')

GKE_API=$$(KUBECONFIG=/home/ubuntu/.kube/config-gke \
  kubectl config view --minify -o jsonpath='{.clusters[0].cluster.server}')

# ─────────────────────────────────────────────────────────────────────────────
# Instance public IP (via IMDSv1)
# ─────────────────────────────────────────────────────────────────────────────
INSTANCE_IP=$$(curl -s http://169.254.169.254/latest/meta-data/public-ipv4)

# ─────────────────────────────────────────────────────────────────────────────
# Write ~/akospace/.env
# ─────────────────────────────────────────────────────────────────────────────
cat > /home/ubuntu/akospace/.env << ENVEOF
K8S1_API_SERVER_HOST=$$EKS_API
K8S1_API_SERVER_TOKEN=$$EKS_TOKEN
K8S2_API_SERVER_HOST=$$GKE_API
K8S2_API_SERVER_TOKEN=$$GKE_TOKEN
AKOFLOW_SERVER_SERVICE_SERVICE_HOST=$$INSTANCE_IP
AKOFLOW_SERVER_SERVICE_SERVICE_PORT=8080
ENVEOF
chown ubuntu:ubuntu /home/ubuntu/akospace/.env
echo "--- .env written ---"
cat /home/ubuntu/akospace/.env | grep -v TOKEN

# ─────────────────────────────────────────────────────────────────────────────
# Run AkoFlow
# ─────────────────────────────────────────────────────────────────────────────
echo "Starting AkoFlow installer..."
cd /home/ubuntu/akospace
curl -fsSL https://akoflow.com/run | bash

echo "=== AkoFlow setup complete at $$(date) ==="
