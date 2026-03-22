<?php

namespace Database\Seeders\Development\TemplateDefinitions;

class AkoFlowEngineK8sDefinition
{
    public static function get(): array
    {
        return [
            'environment_configuration' => [
                'label'       => 'AkoFlow Engine + Kubernetes Settings',
                'description' => 'Configure your AkoFlow Engine and Kubernetes cluster (EKS on AWS or GKE on GCP). '
                    . 'The cluster is provisioned first; the engine VM is then bootstrapped, connects to the cluster '
                    . 'automatically, and writes ~/akospace/.env with all connection settings.',
                'type'        => 'environment',

                // ── Groups ────────────────────────────────────────────────────
                'groups' => [
                    [
                        'name'        => 'infrastructure',
                        'label'       => 'Infrastructure',
                        'description' => 'Cloud provider, region, and Kubernetes cluster settings.',
                        'icon'        => 'server',
                    ],
                    [
                        'name'        => 'engine',
                        'label'       => 'AkoFlow Engine VM',
                        'description' => 'VM that runs the AkoFlow engine, connects to the cluster, '
                            . 'and exposes the AkoFlow API.',
                        'icon'        => 'cpu',
                    ],
                    [
                        'name'        => 'akoflow',
                        'label'       => 'AkoFlow Configuration',
                        'description' => 'Application settings written to ~/akospace/.env on the engine VM.',
                        'icon'        => 'settings',
                    ],
                ],

                'sections' => [

                    // ════════════════════════════════════════════════════════
                    // GROUP: INFRASTRUCTURE
                    // ════════════════════════════════════════════════════════

                    // ── Cloud Provider ────────────────────────────────────────
                    [
                        'name'        => 'cloud',
                        'label'       => 'Cloud Provider',
                        'description' => 'Provider, region, and optional project settings.',
                        'group'       => 'infrastructure',
                        'fields'      => [
                            [
                                'name'     => 'cloud_provider',
                                'label'    => 'Provider',
                                'type'     => 'select',
                                'required' => true,
                                'default'  => 'aws',
                                'options'  => [
                                    ['label' => 'AWS (EKS)', 'value' => 'aws'],
                                    ['label' => 'GCP (GKE)', 'value' => 'gcp'],
                                ],
                            ],
                            [
                                'name'        => 'region',
                                'label'       => 'Region',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => 'us-east-1',
                                'description' => 'AWS region (e.g. us-east-1) or GCP region (e.g. us-central1).',
                            ],
                            [
                                'name'        => 'project_id',
                                'label'       => 'GCP Project ID',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => '',
                                'description' => 'Required when deploying to GCP.',
                            ],
                        ],
                    ],

                    // ── Kubernetes Cluster ────────────────────────────────────
                    [
                        'name'        => 'cluster',
                        'label'       => 'Kubernetes Cluster',
                        'description' => 'EKS (AWS) or GKE (GCP) cluster — version, node type, sizing, and disk.',
                        'group'       => 'infrastructure',
                        'fields'      => [
                            [
                                'name'        => 'eks_cluster_version',
                                'label'       => 'Kubernetes Version (AWS EKS)',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => '1.34',
                                'description' => 'Kubernetes version for the AWS EKS cluster.',
                            ],
                            [
                                'name'        => 'gke_version',
                                'label'       => 'Kubernetes Version (GCP GKE)',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => '1.34',
                                'description' => 'Minimum master version for the GCP GKE cluster.',
                            ],
                            [
                                'name'        => 'node_instance_type',
                                'label'       => 'Node Instance Type (AWS)',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => 't3.medium',
                                'description' => 'EC2 instance type for EKS worker nodes.',
                            ],
                            [
                                'name'        => 'node_machine_type',
                                'label'       => 'Node Machine Type (GCP)',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => 'n1-standard-2',
                                'description' => 'GCE machine type for GKE worker nodes.',
                            ],
                            [
                                'name'        => 'node_count',
                                'label'       => 'Node Count',
                                'type'        => 'number',
                                'required'    => false,
                                'default'     => 1,
                                'description' => 'Desired number of worker nodes.',
                            ],
                            [
                                'name'        => 'node_min_count',
                                'label'       => 'Min Nodes (Autoscaling)',
                                'type'        => 'number',
                                'required'    => false,
                                'default'     => 1,
                            ],
                            [
                                'name'        => 'node_max_count',
                                'label'       => 'Max Nodes (Autoscaling)',
                                'type'        => 'number',
                                'required'    => false,
                                'default'     => 3,
                            ],
                            [
                                'name'        => 'node_disk_size_gb',
                                'label'       => 'Node Disk Size (GB)',
                                'type'        => 'number',
                                'required'    => false,
                                'default'     => 50,
                                'description' => 'Root disk size for each worker node.',
                            ],
                            [
                                'name'        => 'gke_enable_autoscaling',
                                'label'       => 'Enable Autoscaling (GCP)',
                                'type'        => 'boolean',
                                'required'    => false,
                                'default'     => false,
                                'description' => 'Enable node-pool autoscaling on GKE.',
                            ],
                        ],
                    ],

                    // ════════════════════════════════════════════════════════
                    // GROUP: ENGINE VM
                    // ════════════════════════════════════════════════════════

                    // ── Engine VM ─────────────────────────────────────────────
                    [
                        'name'        => 'engine',
                        'label'       => 'Engine VM',
                        'description' => 'VM that hosts the AkoFlow engine. '
                            . 'Docker is installed, `curl -fsSL https://akoflow.com/run | bash` is executed, '
                            . 'kubectl is installed, and the VM automatically connects to the Kubernetes cluster.',
                        'group'       => 'engine',
                        'fields'      => [
                            [
                                'name'        => 'engine_instance_type',
                                'label'       => 'Instance Type (AWS)',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => 't3.medium',
                                'description' => 'EC2 instance type for the AkoFlow engine on AWS.',
                            ],
                            [
                                'name'        => 'engine_machine_type',
                                'label'       => 'Machine Type (GCP)',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => 'n1-standard-2',
                                'description' => 'GCE machine type for the AkoFlow engine on GCP.',
                            ],
                            [
                                'name'        => 'engine_disk_size_gb',
                                'label'       => 'Engine Disk Size (GB)',
                                'type'        => 'number',
                                'required'    => false,
                                'default'     => 50,
                                'description' => 'Root disk size for the engine VM.',
                            ],
                        ],
                    ],

                    // ════════════════════════════════════════════════════════
                    // GROUP: AKOFLOW CONFIGURATION
                    // ════════════════════════════════════════════════════════

                    // ── AkoFlow Settings ──────────────────────────────────────
                    [
                        'name'        => 'akoflow',
                        'label'       => 'AkoFlow Settings',
                        'description' => 'Application settings written to ~/akospace/.env on the engine VM with '
                            . 'K8s connection details resolved automatically after cluster provisioning.',
                        'group'       => 'akoflow',
                        'fields'      => [
                            [
                                'name'        => 'akoflow_env',
                                'label'       => 'Environment',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => 'dev',
                                'description' => 'AKOFLOW_ENV value (e.g. dev, staging, production).',
                            ],
                            [
                                'name'        => 'akoflow_api_port',
                                'label'       => 'AkoFlow API Port',
                                'type'        => 'number',
                                'required'    => true,
                                'default'     => 8080,
                                'description' => 'TCP port the AkoFlow API listens on (AKOFLOW_PORT).',
                            ],
                            [
                                'name'        => 'akoflow_allowed_ips',
                                'label'       => 'Allowed IPs (CIDR)',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => '0.0.0.0/0',
                                'description' => 'CIDR block allowed to reach the AkoFlow API port.',
                            ],
                        ],
                    ],
                ],
            ],

        ];
    }
}
