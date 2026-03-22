<?php

namespace Database\Seeders\Development\TemplateDefinitions;

class AkoFlowEngineK8sDefinition
{
    public static function get(): array
    {
        return [
            'environment_configuration' => [
                'label'       => 'Deployment Settings',
                'description' => 'Configure your AkoFlow Engine + Kubernetes cluster on AWS (EKS) or GCP (GKE).',
                'type'        => 'environment',
                'sections'    => [

                    // ── Cloud Provider ────────────────────────────────────────
                    [
                        'name'        => 'cloud',
                        'label'       => 'Cloud Provider',
                        'description' => 'Provider and region.',
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
                                'name'     => 'region',
                                'label'    => 'Region',
                                'type'     => 'string',
                                'required' => true,
                                'default'  => 'us-east-1',
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
                        'description' => 'EKS (AWS) or GKE (GCP) cluster settings.',
                        'fields'      => [
                            [
                                'name'        => 'eks_cluster_version',
                                'label'       => 'Kubernetes Version (AWS EKS)',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => '1.29',
                                'description' => 'Kubernetes version for the AWS EKS cluster.',
                            ],
                            [
                                'name'        => 'gke_version',
                                'label'       => 'Kubernetes Version (GCP GKE)',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => '1.29',
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
                                'default'     => 2,
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
                                'default'     => 5,
                            ],
                            [
                                'name'        => 'gke_enable_autoscaling',
                                'label'       => 'Enable Autoscaling (GCP)',
                                'type'        => 'boolean',
                                'required'    => false,
                                'default'     => true,
                                'description' => 'Enable node-pool autoscaling on GKE.',
                            ],
                        ],
                    ],

                    // ── AkoFlow Engine ────────────────────────────────────────
                    [
                        'name'        => 'engine',
                        'label'       => 'AkoFlow Engine',
                        'description' => 'VM running the AkoFlow engine. '
                            . 'Docker is installed, `curl -fsSL https://akoflow.com/run | bash` is executed, '
                            . 'kubectl is installed, and the VM automatically connects to the Kubernetes cluster '
                            . 'using a generated service-account token.',
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
                                'name'        => 'akoflow_api_port',
                                'label'       => 'AkoFlow API Port',
                                'type'        => 'number',
                                'required'    => true,
                                'default'     => 8080,
                                'description' => 'TCP port the AkoFlow API listens on.',
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

            'instance_configurations' => [
                'k8s-cluster' => [
                    'label'    => 'Kubernetes Cluster (EKS / GKE)',
                    'type'     => 'kubernetes',
                    'sections' => [],
                ],
                'engine-vm' => [
                    'label'    => 'AkoFlow Engine VM',
                    'type'     => 'vm',
                    'sections' => [],
                ],
            ],
        ];
    }
}
