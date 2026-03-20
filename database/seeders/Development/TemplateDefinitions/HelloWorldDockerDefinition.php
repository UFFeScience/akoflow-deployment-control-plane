<?php

namespace Database\Seeders\Development\TemplateDefinitions;

class HelloWorldDockerDefinition
{
    public static function get(): array
    {
        return [
            'environment_configuration' => [
                'label'       => 'Deployment Settings',
                'description' => 'Parâmetros básicos para subir uma única VM com Docker.',
                'type'        => 'environment',
                'sections'    => [
                    [
                        'name'        => 'cloud',
                        'label'       => 'Cloud Provider',
                        'description' => 'Configurações do provedor.',
                        'fields'      => [
                            [
                                'name'        => 'cloud_provider',
                                'label'       => 'Provider',
                                'type'        => 'select',
                                'required'    => true,
                                'default'     => 'aws',
                                'options'     => [
                                    ['label' => 'AWS', 'value' => 'aws'],
                                    ['label' => 'GCP', 'value' => 'gcp'],
                                ],
                            ],
                            [
                                'name'        => 'region',
                                'label'       => 'Region',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => 'us-east-1',
                            ],
                            [
                                'name'        => 'zone',
                                'label'       => 'Zone (GCP)',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => 'us-east1-b',
                            ],
                        ],
                    ],
                    [
                        'name'        => 'instance',
                        'label'       => 'Instance',
                        'description' => 'Tamanho e imagem.',
                        'fields'      => [
                            [
                                'name'        => 'instance_type',
                                'label'       => 'Instance Type',
                                'type'        => 'string',
                                'required'    => true,
                                'default'     => 't3.micro',
                            ],
                            [
                                'name'        => 'machine_type_gcp',
                                'label'       => 'Machine Type (GCP)',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => 'e2-micro',
                            ],
                            [
                                'name'        => 'ami_id',
                                'label'       => 'AMI ID (AWS)',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => 'ami-0c02fb55956c7d316',
                            ],
                            [
                                'name'        => 'image_gcp',
                                'label'       => 'Image (GCP)',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => 'projects/debian-cloud/global/images/family/debian-12',
                            ],
                        ],
                    ],
                    [
                        'name'        => 'startup',
                        'label'       => 'Startup Script',
                        'description' => 'Script executado no boot da VM.',
                        'fields'      => [
                            [
                                'name'        => 'startup_script',
                                'label'       => 'Startup Script',
                                'type'        => 'script',
                                'required'    => true,
                                'default'     => "#!/bin/bash\nset -eux\napt-get update || yum update -y || true\napt-get install -y docker.io || yum install -y docker || true\nsystemctl enable docker || true\nsystemctl start docker || true\necho 'hello world' > /tmp/hello.txt\ndocker run --rm hello-world || true\n",
                            ],
                        ],
                    ],
                ],
            ],
            'instance_configurations' => [
                'single-vm' => [
                    'label'   => 'Single VM',
                    'type'    => 'vm',
                    'sections'=> [],
                ],
            ],
        ];
    }
}
