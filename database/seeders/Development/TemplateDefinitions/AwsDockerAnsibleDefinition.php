<?php

namespace Database\Seeders\Development\TemplateDefinitions;

class AwsDockerAnsibleDefinition
{
    public static function get(): array
    {
        return [
            'providers'          => ['aws'],
            'required_providers' => ['aws'],

            'environment_configuration' => [
                'label'       => 'AWS EC2 + Docker',
                'description' => 'Provisions an EC2 instance on AWS via Terraform and installs Docker via Ansible.',
                'type'        => 'environment',

                'sections' => [

                    [
                        'name'        => 'cloud',
                        'label'       => 'Cloud Provider',
                        'description' => 'AWS region and instance configuration.',
                        'fields'      => [
                            [
                                'name'     => 'region',
                                'label'    => 'AWS Region',
                                'type'     => 'string',
                                'required' => true,
                                'default'  => 'us-east-1',
                            ],
                            [
                                'name'     => 'instance_type',
                                'label'    => 'Instance Type',
                                'type'     => 'string',
                                'required' => false,
                                'default'  => 't3.micro',
                            ],
                            [
                                'name'        => 'key_name',
                                'label'       => 'Key Pair Name',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => '',
                                'description' => 'Name of the AWS Key Pair used for SSH access. The corresponding private key must be stored in the provider credential.',
                            ],
                        ],
                    ],

                    [
                        'name'        => 'ansible',
                        'label'       => 'Ansible Configuration',
                        'description' => 'Settings used by Ansible to connect and configure the instance.',
                        'fields'      => [
                            [
                                'name'        => 'ssh_user',
                                'label'       => 'SSH User',
                                'type'        => 'string',
                                'required'    => false,
                                'default'     => 'ubuntu',
                                'description' => 'OS user Ansible will connect as. Use ubuntu for Ubuntu AMIs or ec2-user for Amazon Linux.',
                            ],
                        ],
                    ],

                ],
            ],
        ];
    }
}
