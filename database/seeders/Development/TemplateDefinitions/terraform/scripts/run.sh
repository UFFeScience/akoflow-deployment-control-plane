#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# AkôCloud – Terraform runner
# Usage: run.sh <action> <workspace_dir> [extra_terraform_args...]
#   action       : apply | destroy | plan
#   workspace_dir: absolute path to the environment workspace
# ---------------------------------------------------------------------------
set -euo pipefail

ACTION="${1:-apply}"
WORKSPACE="${2:-/workspace}"
shift 2 || true
EXTRA_ARGS=("$@")

TF_IMAGE="${TF_DOCKER_IMAGE:-hashicorp/terraform:1.9}"

echo "==> [akocloud] action=${ACTION} workspace=${WORKSPACE}"

run_tf() {
    docker run --rm \
        -v "${WORKSPACE}:/workspace" \
        -w /workspace \
        --env-file "${WORKSPACE}/.env" \
        "${TF_IMAGE}" "$@"
}

# ── init ─────────────────────────────────────────────────────────────────────
echo "==> [akocloud] terraform init"
run_tf init -input=false -no-color

# ── plan ─────────────────────────────────────────────────────────────────────
echo "==> [akocloud] terraform plan"
run_tf plan \
    -var-file="terraform.tfvars.json" \
    -out=tfplan \
    -input=false \
    -no-color

if [[ "${ACTION}" == "plan" ]]; then
    echo "==> [akocloud] plan-only mode – exiting"
    exit 0
fi

# ── apply / destroy ───────────────────────────────────────────────────────────
if [[ "${ACTION}" == "destroy" ]]; then
    echo "==> [akocloud] terraform destroy"
    run_tf destroy \
        -var-file="terraform.tfvars.json" \
        -auto-approve \
        -input=false \
        -no-color \
        "${EXTRA_ARGS[@]}"
else
    echo "==> [akocloud] terraform apply"
    run_tf apply \
        -input=false \
        -no-color \
        "${EXTRA_ARGS[@]}" \
        tfplan
fi

# ── capture outputs ───────────────────────────────────────────────────────────
echo "==> [akocloud] terraform output"
run_tf output -json -no-color > "${WORKSPACE}/outputs.json" 2>/dev/null || true

echo "==> [akocloud] done"
