<?php

namespace Database\Seeders\Development;

use App\Models\EnvironmentTemplate;
use App\Models\EnvironmentTemplateAnsiblePlaybook;
use App\Models\EnvironmentTemplateProviderConfiguration;
use App\Models\EnvironmentTemplateVersion;
use Illuminate\Database\Seeder;

class TemplateAnsiblePlaybooksSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->playbooks() as $entry) {
            $versionId = $this->resolveVersionId($entry['template_slug'], $entry['template_version']);

            if ($versionId === null) {
                $this->command->warn(
                    "Skipping playbook '{$entry['playbook_slug']}': template '{$entry['template_slug']}' v{$entry['template_version']} not found."
                );
                continue;
            }

            $providerType = strtoupper($entry['provider_type']);

            $config = EnvironmentTemplateProviderConfiguration::firstOrCreate(
                ['template_version_id' => $versionId, 'name' => $providerType],
                ['applies_to_providers' => [$providerType]],
            );

            EnvironmentTemplateAnsiblePlaybook::updateOrCreate(
                ['provider_configuration_id' => $config->id],
                [
                    'playbook_slug'        => $entry['playbook_slug'],
                    'playbook_yaml'        => $entry['playbook_yaml'],
                    'inventory_template'   => $entry['inventory_template'] ?? null,
                    'vars_mapping_json'    => $entry['vars_mapping_json'],
                    'outputs_mapping_json' => $entry['outputs_mapping_json'],
                    'credential_env_keys'  => $entry['credential_env_keys'],
                    'roles_json'           => $entry['roles_json'] ?? [],
                ],
            );
        }
    }

    private function resolveVersionId(string $templateSlug, string $version): ?int
    {
        $template = EnvironmentTemplate::where('slug', $templateSlug)->first();
        if (! $template) {
            return null;
        }

        return EnvironmentTemplateVersion::where('template_id', $template->id)
            ->where('version', $version)
            ->value('id');
    }

    private function playbooks(): array
    {
        return [
            $this->awsDockerInstall(),
        ];
    }

    private function awsDockerInstall(): array
    {
        return [
            'template_slug'    => 'aws-docker-ansible',
            'template_version' => '1.0.0',
            'provider_type'    => 'aws',
            'playbook_slug'    => 'aws-docker-install',

            'playbook_yaml' => <<<'YAML'
- name: Install Docker on AWS EC2 instance
  hosts: all
  become: true

  tasks:
    - name: Update apt cache
      apt:
        update_cache: yes
        cache_valid_time: 3600

    - name: Install prerequisites
      apt:
        name:
          - ca-certificates
          - curl
          - gnupg
          - lsb-release
        state: present

    - name: Add Docker GPG key
      apt_key:
        url: https://download.docker.com/linux/ubuntu/gpg
        state: present

    - name: Add Docker APT repository
      apt_repository:
        repo: "deb [arch=amd64] https://download.docker.com/linux/ubuntu {{ ansible_distribution_release }} stable"
        state: present
        update_cache: yes

    - name: Install Docker Engine and Compose plugin
      apt:
        name:
          - docker-ce
          - docker-ce-cli
          - containerd.io
          - docker-buildx-plugin
          - docker-compose-plugin
        state: present

    - name: Start and enable Docker service
      systemd:
        name: docker
        state: started
        enabled: yes

    - name: Write ansible_outputs.json
      copy:
        dest: /tmp/ansible_outputs.json
        content: |
          {
            "docker_version": "{{ ansible_facts.packages['docker-ce'][0].version | default('installed') }}"
          }
YAML,

            'inventory_template' => null,

            'vars_mapping_json' => [
                'environment_configuration' => [
                    'ssh_user' => 'ansible_user',
                ],
            ],

            'outputs_mapping_json' => [
                'resources' => [
                    [
                        'name'                  => 'docker-host',
                        'ansible_resource_type' => 'docker_install',
                        'outputs'               => [
                            'metadata' => [
                                'docker_version' => 'docker_version',
                            ],
                        ],
                    ],
                ],
            ],

            'credential_env_keys' => [],

            'roles_json' => [],
        ];
    }
}
