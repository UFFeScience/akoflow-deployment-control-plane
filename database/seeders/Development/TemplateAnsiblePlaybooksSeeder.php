<?php

namespace Database\Seeders\Development;

use App\Models\AnsiblePlaybookTask;
use App\Models\EnvironmentTemplate;
use App\Models\EnvironmentTemplateAnsiblePlaybook;
use App\Models\EnvironmentTemplateProviderConfiguration;
use App\Models\EnvironmentTemplateRunbook;
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

            $playbook = EnvironmentTemplateAnsiblePlaybook::updateOrCreate(
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

            // Seed structured tasks for this playbook (idempotent: clear + re-insert)
            if (!empty($entry['tasks'])) {
                $playbook->tasks()->delete();
                foreach ($entry['tasks'] as $i => $taskData) {
                    AnsiblePlaybookTask::create(array_merge($taskData, [
                        'ansible_playbook_id' => $playbook->id,
                        'position'            => $taskData['position'] ?? $i,
                    ]));
                }
            }

            // Seed sample runbooks for this provider config
            foreach ($entry['runbooks'] ?? [] as $runbookData) {
                $runbook = EnvironmentTemplateRunbook::updateOrCreate(
                    [
                        'provider_configuration_id' => $config->id,
                        'name'                      => $runbookData['name'],
                    ],
                    [
                        'description'         => $runbookData['description'] ?? null,
                        'playbook_yaml'       => $runbookData['playbook_yaml'] ?? null,
                        'vars_mapping_json'   => $runbookData['vars_mapping_json'] ?? null,
                        'credential_env_keys' => $runbookData['credential_env_keys'] ?? [],
                        'roles_json'          => $runbookData['roles_json'] ?? [],
                        'position'            => $runbookData['position'] ?? 0,
                    ],
                );

                if (!empty($runbookData['tasks'])) {
                    $runbook->tasks()->delete();
                    foreach ($runbookData['tasks'] as $i => $taskData) {
                        AnsiblePlaybookTask::create(array_merge($taskData, [
                            'runbook_id' => $runbook->id,
                            'position'   => $taskData['position'] ?? $i,
                        ]));
                    }
                }
            }
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

            'credential_env_keys' => ['SSH_PRIVATE_KEY'],

            'roles_json' => [],

            // ── Structured tasks (stored in ansible_playbook_tasks table) ────
            'tasks' => [
                ['name' => 'Update apt cache',                    'module' => 'apt'],
                ['name' => 'Install prerequisites',               'module' => 'apt'],
                ['name' => 'Add Docker GPG key',                  'module' => 'apt_key'],
                ['name' => 'Add Docker APT repository',           'module' => 'apt_repository'],
                ['name' => 'Install Docker Engine and Compose plugin', 'module' => 'apt'],
                ['name' => 'Start and enable Docker service',     'module' => 'systemd'],
                ['name' => 'Write ansible_outputs.json',          'module' => 'copy'],
            ],

            // ── Sample standalone runbooks ────────────────────────────────────
            'runbooks' => [
                [
                    'name'        => 'Restart Docker',
                    'description' => 'Restarts the Docker daemon on the provisioned instance.',
                    'position'    => 0,
                    'playbook_yaml' => <<<'YAML'
- name: Restart Docker
  hosts: all
  become: true

  tasks:
    - name: Restart Docker service
      systemd:
        name: docker
        state: restarted

    - name: Verify Docker is running
      systemd:
        name: docker
        state: started
        enabled: yes
YAML,
                    'vars_mapping_json'   => ['environment_configuration' => ['ssh_user' => 'ansible_user']],
                    'credential_env_keys' => ['SSH_PRIVATE_KEY'],
                    'tasks' => [
                        ['name' => 'Restart Docker service', 'module' => 'systemd'],
                        ['name' => 'Verify Docker is running', 'module' => 'systemd'],
                    ],
                ],
                [
                    'name'        => 'Update System Packages',
                    'description' => 'Runs apt upgrade to update all system packages on the instance.',
                    'position'    => 1,
                    'playbook_yaml' => <<<'YAML'
- name: Update System Packages
  hosts: all
  become: true

  tasks:
    - name: Update apt cache
      apt:
        update_cache: yes
        cache_valid_time: 0

    - name: Upgrade all packages
      apt:
        upgrade: dist
        autoremove: yes

    - name: Check if reboot is required
      stat:
        path: /var/run/reboot-required
      register: reboot_required

    - name: Print reboot status
      debug:
        msg: "Reboot required: {{ reboot_required.stat.exists }}"
YAML,
                    'vars_mapping_json'   => ['environment_configuration' => ['ssh_user' => 'ansible_user']],
                    'credential_env_keys' => ['SSH_PRIVATE_KEY'],
                    'tasks' => [
                        ['name' => 'Update apt cache',          'module' => 'apt'],
                        ['name' => 'Upgrade all packages',      'module' => 'apt'],
                        ['name' => 'Check if reboot is required', 'module' => 'stat'],
                        ['name' => 'Print reboot status',       'module' => 'debug'],
                    ],
                ],
            ],
        ];
    }
}
