<?php

namespace Database\Seeders\Development\TemplateDefinitions;

class Sscad2025FedLearningDefinition
{
    public static function get(): array
    {
        return [
            'providers'          => ['gcp'],
            'required_providers' => ['gcp'],

            'environment_configuration' => [
                'label'       => 'SSCAD 2025 Federated Learning (GCP)',
                'description' => 'Provisions the SSCAD 2025 federated-learning topology on GCP. Terraform handles the VMs and a minimal bootstrap, while trigger-aware Ansible playbooks handle startup and post-execution on the provisioned hosts.',
                'type'        => 'environment',

                'groups' => [
                    [
                        'name'        => 'cloud',
                        'label'       => 'Cloud',
                        'description' => 'GCP project, region, zone, network and image used by the environment.',
                        'icon'        => 'network',
                    ],
                    [
                        'name'        => 'experiment',
                        'label'       => 'Experiment',
                        'description' => 'Experiment metadata mirrored from the SSCAD reference file.',
                        'icon'        => 'settings',
                    ],
                    [
                        'name'        => 'instances',
                        'label'       => 'Instances',
                        'description' => 'Machine types for DfAnalyse, Overseer, Server and Sites.',
                        'icon'        => 'server',
                    ],
                    [
                        'name'        => 'execution',
                        'label'       => 'Configuration',
                        'description' => 'Ansible playbook trigger settings and the bootstrap user used by the merged configuration step.',
                        'icon'        => 'play',
                    ],
                ],

                'sections' => [
                    [
                        'name'        => 'cloud',
                        'label'       => 'GCP Cloud',
                        'description' => 'Project, region, zone, subnet and image for all SSCAD nodes.',
                        'group'       => 'cloud',
                        'fields'      => [
                            [
                                'name'     => 'project_id',
                                'label'    => 'Project ID',
                                'type'     => 'string',
                                'required' => true,
                                'default'  => '',
                            ],
                            [
                                'name'     => 'region',
                                'label'    => 'Region',
                                'type'     => 'string',
                                'required' => true,
                                'default'  => 'us-east1',
                            ],
                            [
                                'name'     => 'zone',
                                'label'    => 'Zone',
                                'type'     => 'string',
                                'required' => true,
                                'default'  => 'us-east1-b',
                            ],
                            [
                                'name'        => 'network_name',
                                'label'       => 'Network Name',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => 'default',
                                'description' => 'GCP VPC network used by the Compute Engine instances.',
                            ],
                            [
                                'name'        => 'subnet_name',
                                'label'       => 'Subnet Name',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => 'default',
                                'description' => 'GCP subnet used by the environment.',
                            ],
                            [
                                'name'        => 'image_id',
                                'label'       => 'Boot Image',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => 'projects/ubuntu-os-cloud/global/images/family/ubuntu-2204-lts',
                                'description' => 'Boot image self-link used by all instances.',
                            ],
                            [
                                'name'        => 'ssh_public_key',
                                'label'       => 'SSH Public Key',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => '',
                                'description' => 'Optional SSH public key to attach to the Compute Engine instances.',
                            ],
                        ],
                    ],
                    [
                        'name'        => 'experiment',
                        'label'       => 'Experiment Metadata',
                        'description' => 'Values that change between SSCAD experiments.',
                        'group'       => 'experiment',
                        'fields'      => [
                            [
                                'name'        => 'experiment_name',
                                'label'       => 'Experiment Name',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => 'ccpe-2026-c1',
                                'description' => 'Experiment identifier used to prefix the resources.',
                            ],
                            [
                                'name'        => 'description',
                                'label'       => 'Description',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => 'Scenario 1: n2-highmem-16 Server + n2-standard-16 Clients',
                                'description' => 'Short human-readable description of the experiment scenario.',
                            ],
                            [
                                'name'        => 'algorithm',
                                'label'       => 'Algorithm',
                                'type'        => 'select',
                                'required'    => true,
                                'default'     => 'dbscan',
                                'options'     => [
                                    ['label' => 'DBSCAN', 'value' => 'dbscan'],
                                    ['label' => 'KMeans', 'value' => 'kmeans'],
                                ],
                            ],
                            [
                                'name'     => 'clients',
                                'label'    => 'Clients',
                                'type'     => 'number',
                                'required' => true,
                                'default'  => 10,
                                'min'      => 1,
                                'max'      => 100,
                            ],
                            [
                                'name'        => 'dataset_folder_key',
                                'label'       => 'Dataset Folder Key',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => 'https://storage.googleapis.com/outliers-ccpe-2026/dataset/sample_desdr2',
                                'description' => 'Base GCS path for the per-site dataset CSV files.',
                            ],
                            [
                                'name'        => 'site_folder_url',
                                'label'       => 'Site Workspace ZIP',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => 'https://storage.googleapis.com/outliers-ccpe-2026/infra-sscad-2/prod_01.zip',
                                'description' => 'Zip file with the NVFlare workspace used by the Sites.',
                            ],
                        ],
                    ],
                    [
                        'name'        => 'instances',
                        'label'       => 'Machine Types',
                        'description' => 'Machine sizes used by the SSCAD topology.',
                        'group'       => 'instances',
                        'fields'      => [
                            [
                                'name'        => 'dfanalyse_machine_type',
                                'label'       => 'DfAnalyse Machine Type',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => 'e2-standard-4',
                                'description' => 'Machine type used by the DfAnalyse node.',
                            ],
                            [
                                'name'        => 'overseer_machine_type',
                                'label'       => 'Overseer Machine Type',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => 'n2-highmem-16',
                                'description' => 'Machine type used by the Overseer node.',
                            ],
                            [
                                'name'        => 'server_machine_type',
                                'label'       => 'Server Machine Type',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => 'n2-highmem-16',
                                'description' => 'Machine type used by the Server node.',
                            ],
                            [
                                'name'        => 'site_machine_type',
                                'label'       => 'Site Machine Type',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => 'n2-standard-16',
                                'description' => 'Machine type used by the site fleet.',
                            ],
                        ],
                    ],
                    [
                        'name'        => 'execution',
                        'label'       => 'Ansible Configuration',
                        'description' => 'Only the bootstrap user lives here. Triggered playbooks define the execution order.',
                        'group'       => 'execution',
                        'fields'      => [
                            [
                                'name'        => 'ssh_user',
                                'label'       => 'SSH User',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => 'ubuntu',
                                'description' => 'User Ansible will use once Terraform bootstrap has prepared the nodes.',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}