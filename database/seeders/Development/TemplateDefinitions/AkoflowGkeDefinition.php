<?php

namespace Database\Seeders\Development\TemplateDefinitions;

class AkoflowGkeDefinition
{
    public static function get(): array
    {
        return [
            'environment_configuration' => [
                'label'       => 'Environment Configuration',
                'description' => 'High-level environment settings that apply to the entire cluster',
                'type'        => 'environment',
                'sections'    => [
                    [
                        'name'        => 'gcp_general',
                        'label'       => 'Google Cloud Platform',
                        'description' => 'General GCP and GKE configuration shared across all instances',
                        'fields'      => [
                            [
                                'name'        => 'gcp_project_id',
                                'label'       => 'GCP Project ID',
                                'type'        => 'string',
                                'required'    => true,
                                'description' => 'Your Google Cloud Project ID',
                            ],
                            [
                                'name'     => 'gcp_region',
                                'label'    => 'GCP Region',
                                'type'     => 'select',
                                'required' => true,
                                'default'  => 'us-central1',
                                'options'  => [
                                    ['label' => 'us-central1',     'value' => 'us-central1'],
                                    ['label' => 'us-west1',        'value' => 'us-west1'],
                                    ['label' => 'us-east1',        'value' => 'us-east1'],
                                    ['label' => 'europe-west1',    'value' => 'europe-west1'],
                                    ['label' => 'asia-southeast1', 'value' => 'asia-southeast1'],
                                ],
                            ],
                            [
                                'name'        => 'gke_version',
                                'label'       => 'GKE Version',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => '1.27',
                                'description' => 'Kubernetes version to use',
                            ],
                        ],
                    ],
                    [
                        'name'        => 'akoflow_basics',
                        'label'       => 'AkoFlow Runtime Basics',
                        'description' => 'Baseline configuration shared by AkoFlow and cluster nodes',
                        'fields'      => [
                            [
                                'name'        => 'akoflow_bootstrap_command',
                                'label'       => 'Bootstrap Command',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => 'curl -fsSL https://akoflow.com/run | bash',
                                'description' => 'Command executed on AkoFlow instance during provisioning',
                            ],
                            [
                                'name'        => 'akoflow_allowed_ips',
                                'label'       => 'Allowed IPs (CSV)',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => '10.0.0.0/24,192.168.0.0/24',
                                'description' => 'Comma-separated list of IP/CIDR ranges allowed to reach AkoFlow',
                            ],
                        ],
                    ],
                ],
            ],

            'instance_configurations' => [
                'gke-compute' => [
                    'label'       => 'GKE Cluster Configuration',
                    'description' => 'Configuration for GKE cluster and node pools – manages Kubernetes infrastructure',
                    'type'        => 'cluster',
                    'position'    => 1,
                    'sections'    => [
                        [
                            'name'        => 'gke_pool_config',
                            'label'       => 'GKE Node Pool Configuration',
                            'description' => 'Configure GKE node pool settings',
                            'fields'      => [
                                [
                                    'name'        => 'node_count',
                                    'label'       => 'Number of Nodes',
                                    'type'        => 'number',
                                    'required'    => true,
                                    'default'     => 5,
                                    'min'         => 1,
                                    'max'         => 100,
                                    'description' => 'Initial number of nodes in this pool',
                                ],
                                [
                                    'name'        => 'machine_type',
                                    'label'       => 'Machine Type',
                                    'type'        => 'select',
                                    'required'    => true,
                                    'default'     => 'n1-standard-4',
                                    'description' => 'GCP machine type for nodes',
                                    'options'     => [
                                        ['label' => 'n1-standard-1 (1 vCPU, 3.75 GB)',  'value' => 'n1-standard-1'],
                                        ['label' => 'n1-standard-2 (2 vCPU, 7.5 GB)',   'value' => 'n1-standard-2'],
                                        ['label' => 'n1-standard-4 (4 vCPU, 15 GB)',    'value' => 'n1-standard-4'],
                                        ['label' => 'n1-standard-8 (8 vCPU, 30 GB)',    'value' => 'n1-standard-8'],
                                        ['label' => 'n1-highmem-4 (4 vCPU, 26 GB)',     'value' => 'n1-highmem-4'],
                                        ['label' => 'n1-highmem-8 (8 vCPU, 52 GB)',     'value' => 'n1-highmem-8'],
                                    ],
                                ],
                                [
                                    'name'     => 'disk_size_gb',
                                    'label'    => 'Boot Disk Size (GB)',
                                    'type'     => 'number',
                                    'required' => true,
                                    'default'  => 100,
                                    'min'      => 10,
                                    'max'      => 1000,
                                ],
                            ],
                        ],
                        [
                            'name'        => 'autoscaling',
                            'label'       => 'Autoscaling Configuration',
                            'description' => 'Configure node pool autoscaling behavior',
                            'fields'      => [
                                [
                                    'name'     => 'enable_autoscaling',
                                    'label'    => 'Enable Autoscaling',
                                    'type'     => 'boolean',
                                    'required' => true,
                                    'default'  => true,
                                ],
                                [
                                    'name'     => 'min_nodes',
                                    'label'    => 'Minimum Nodes',
                                    'type'     => 'number',
                                    'required' => false,
                                    'default'  => 1,
                                    'min'      => 1,
                                ],
                                [
                                    'name'     => 'max_nodes',
                                    'label'    => 'Maximum Nodes',
                                    'type'     => 'number',
                                    'required' => false,
                                    'default'  => 10,
                                    'min'      => 1,
                                ],
                            ],
                        ],
                    ],
                ],

                'akoflow-compute' => [
                    'label'       => 'AkoFlow Instance Configuration',
                    'description' => 'Configuration for AkoFlow application server instance – manages compute engine settings',
                    'type'        => 'instance',
                    'position'    => 2,
                    'sections'    => [
                        [
                            'name'        => 'akoflow_deployment',
                            'label'       => 'AkoFlow Deployment',
                            'description' => 'AkoFlow deployment and execution mode settings',
                            'fields'      => [
                                [
                                    'name'        => 'akoflow_version',
                                    'label'       => 'AkoFlow Version',
                                    'type'        => 'string',
                                    'required'    => true,
                                    'default'     => '1.0.0',
                                    'description' => 'AkoFlow release version to deploy',
                                ],
                                [
                                    'name'        => 'deployment_mode',
                                    'label'       => 'Deployment Mode',
                                    'type'        => 'select',
                                    'required'    => true,
                                    'default'     => 'standard',
                                    'description' => 'How AkoFlow should run',
                                    'options'     => [
                                        ['label' => 'Standard (single instance)', 'value' => 'standard'],
                                        ['label' => 'High Availability',          'value' => 'ha'],
                                        ['label' => 'Distributed',                'value' => 'distributed'],
                                    ],
                                ],
                                [
                                    'name'        => 'replicas',
                                    'label'       => 'Number of Replicas',
                                    'type'        => 'number',
                                    'required'    => true,
                                    'default'     => 1,
                                    'min'         => 1,
                                    'max'         => 10,
                                    'description' => 'Number of AkoFlow server instances',
                                ],
                            ],
                        ],
                        [
                            'name'        => 'compute_resources',
                            'label'       => 'Compute Resources',
                            'description' => 'Server compute resource configuration',
                            'fields'      => [
                                [
                                    'name'        => 'machine_type',
                                    'label'       => 'Machine Type',
                                    'type'        => 'select',
                                    'required'    => true,
                                    'default'     => 'n1-highmem-8',
                                    'description' => 'GCP machine type for AkoFlow server',
                                    'options'     => [
                                        ['label' => 'n1-highmem-4 (4 vCPU, 26 GB)',    'value' => 'n1-highmem-4'],
                                        ['label' => 'n1-highmem-8 (8 vCPU, 52 GB)',    'value' => 'n1-highmem-8'],
                                        ['label' => 'n1-highmem-16 (16 vCPU, 104 GB)', 'value' => 'n1-highmem-16'],
                                    ],
                                ],
                                [
                                    'name'        => 'disk_size_gb',
                                    'label'       => 'Boot Disk Size (GB)',
                                    'type'        => 'number',
                                    'required'    => true,
                                    'default'     => 200,
                                    'min'         => 50,
                                    'max'         => 2000,
                                    'description' => 'Boot disk for AkoFlow instance',
                                ],
                            ],
                        ],
                        [
                            'name'        => 'networking',
                            'label'       => 'Networking Configuration',
                            'description' => 'Network and connectivity settings for AkoFlow server',
                            'fields'      => [
                                [
                                    'name'        => 'enable_public_ip',
                                    'label'       => 'Assign Public IP Address',
                                    'type'        => 'boolean',
                                    'required'    => true,
                                    'default'     => true,
                                    'description' => 'Assign external IP for direct access from the internet',
                                ],
                                [
                                    'name'        => 'api_port',
                                    'label'       => 'API Port',
                                    'type'        => 'number',
                                    'required'    => true,
                                    'default'     => 8080,
                                    'min'         => 1024,
                                    'max'         => 65535,
                                    'description' => 'Port where AkoFlow API will listen',
                                ],
                                [
                                    'name'        => 'enable_https',
                                    'label'       => 'Enable HTTPS/SSL',
                                    'type'        => 'boolean',
                                    'required'    => true,
                                    'default'     => true,
                                    'description' => 'Enable HTTPS for secure API communication',
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            'cluster_topology' => [
                'description'    => 'AkoFlow on GKE: Complete cluster with GKE nodes and AkoFlow server instance',
                'instance_groups' => [
                    [
                        'name'                         => 'gke-nodes',
                        'label'                        => 'GKE Compute Nodes',
                        'description'                  => 'Google Kubernetes Engine cluster node pool',
                        'instance_group_template_slug' => 'gke-compute',
                        'quantity'                     => 5,
                        'default_terraform_variables'  => [
                            'node_count'        => 5,
                            'machine_type'      => 'n1-standard-4',
                            'disk_size_gb'      => 100,
                            'enable_autoscaling' => true,
                            'min_nodes'         => 1,
                            'max_nodes'         => 10,
                        ],
                    ],
                    [
                        'name'                         => 'akoflow',
                        'label'                        => 'AkoFlow Server',
                        'description'                  => 'AkoFlow application server and compute engine instance',
                        'instance_group_template_slug' => 'akoflow-compute',
                        'quantity'                     => 1,
                        'default_terraform_variables'  => [
                            'akoflow_version'  => '1.0.0',
                            'deployment_mode'  => 'standard',
                            'replicas'         => 1,
                            'machine_type'     => 'n1-highmem-8',
                            'disk_size_gb'     => 200,
                            'enable_public_ip' => true,
                            'api_port'         => 8080,
                            'enable_https'     => true,
                        ],
                    ],
                ],
            ],
        ];
    }
}
