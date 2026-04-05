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
                ['provider_configuration_id' => $config->id, 'phase' => 'provision'],
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

            // Seed optional teardown playbook
            if (!empty($entry['teardown_playbook_yaml'])) {
                EnvironmentTemplateAnsiblePlaybook::updateOrCreate(
                    ['provider_configuration_id' => $config->id, 'phase' => 'teardown'],
                    [
                        'playbook_slug'        => ($entry['playbook_slug'] ?? '') . '-teardown',
                        'playbook_yaml'        => $entry['teardown_playbook_yaml'],
                        'inventory_template'   => $entry['teardown_inventory_template'] ?? $entry['inventory_template'] ?? null,
                        'vars_mapping_json'    => $entry['teardown_vars_mapping_json'] ?? $entry['vars_mapping_json'],
                        'outputs_mapping_json' => [],
                        'credential_env_keys'  => $entry['credential_env_keys'],
                        'roles_json'           => $entry['teardown_roles_json'] ?? [],
                    ],
                );
            }

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
            $this->localAkoflowInstall(),
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

    // ─────────────────────────────────────────────────────────────────────────
    // LOCAL — AkôFlow Installer
    // ─────────────────────────────────────────────────────────────────────────

    private function localAkoflowInstall(): array
    {
        return [
            'template_slug'    => 'akoflow-local-installer',
            'template_version' => '1.0.0',
            'provider_type'    => 'local',
            'playbook_slug'    => 'local-akoflow-install',

            // inventory is auto-generated from the ProvisionedResource created by Terraform
            // (public_ip = host address output by null_resource.verify_host)
            'inventory_template' => null,

            'playbook_yaml' => <<<'YAML'
- name: Install AkôFlow on remote host via Docker
  hosts: all
  become: false
  environment:
    PATH: "/usr/local/bin:/usr/bin:/bin:/opt/homebrew/bin:{{ ansible_env.HOME }}/.local/bin:{{ ansible_env.PATH | default('') }}"
  vars:
    akoflow_port: "{{ akoflow_port | default('8080') }}"
    akospace_dir: "{{ akospace_dir | default(ansible_env.HOME + '/akospace') }}"

  tasks:
    - name: Check Docker is installed
      shell: docker --version
      register: docker_check
      changed_when: false
      failed_when: docker_check.rc != 0

    - name: Create akospace directory
      file:
        path: "{{ akospace_dir }}"
        state: directory
        mode: '0755'

    - name: Create empty database file if not present
      file:
        path: "{{ akospace_dir }}/database.db"
        state: touch
        modification_time: preserve
        access_time: preserve

    - name: Create empty log file if not present
      file:
        path: "{{ akospace_dir }}/ako.log"
        state: touch
        modification_time: preserve
        access_time: preserve

    - name: Create .env file (skip if already exists)
      copy:
        dest: "{{ akospace_dir }}/.env"
        content: |
          AKOFLOW_ENV=dev
          AKOFLOW_PORT={{ akoflow_port }}
        force: no

    - name: Get latest AkôFlow release tag
      shell: >
        curl -fsSL https://api.github.com/repos/UFFeScience/akoflow/releases/latest
        | grep tag_name | cut -d '"' -f 4 | sed 's/^v//'
      register: akoflow_tag
      changed_when: false

    - name: Detect host architecture
      command: uname -m
      register: host_arch
      changed_when: false

    - name: Set binary architecture
      set_fact:
        barch: "{{ 'arm64' if host_arch.stdout in ['aarch64', 'arm64'] else 'amd64' }}"

    - name: Create Docker build directory
      file:
        path: /tmp/akoflow-build
        state: directory
        mode: '0755'

    - name: Write Dockerfile
      copy:
        dest: /tmp/akoflow-build/Dockerfile
        content: |
          FROM debian:bookworm-slim

          RUN apt-get update && apt-get install -y \
              curl \
              ca-certificates \
              unzip \
              sqlite3 \
              ssh \
              sshpass \
              rsync \
           && rm -rf /var/lib/apt/lists/*

          WORKDIR /app

          RUN set -eux; \
              TAG={{ akoflow_tag.stdout }}; \
              BARCH={{ barch }}; \
              curl -fsSL -o /usr/local/bin/akoflow-server \
                "https://github.com/UFFeScience/akoflow/releases/download/v${TAG}/akoflow-server_${TAG}_linux_${BARCH}"; \
              curl -fsSL -o /usr/local/bin/akoflow \
                "https://github.com/UFFeScience/akoflow/releases/download/v${TAG}/akoflow-client_${TAG}_linux_${BARCH}"; \
              chmod +x /usr/local/bin/akoflow-server /usr/local/bin/akoflow; \
              curl -fsSL -o source.zip \
                "https://github.com/UFFeScience/akoflow/archive/refs/tags/v${TAG}.zip"; \
              unzip -qq source.zip "akoflow-${TAG}/pkg/server/engine/httpserver/handlers/akoflow_admin_handler/*"; \
              unzip -qq source.zip "akoflow-${TAG}/pkg/server/scripts/*"; \
              mkdir -p /app/pkg/server/engine/httpserver/handlers; \
              mv "akoflow-${TAG}/pkg/server/engine/httpserver/handlers/akoflow_admin_handler" \
                /app/pkg/server/engine/httpserver/handlers/; \
              mv "akoflow-${TAG}/pkg/server/scripts/" /app/pkg/server/; \
              rm -rf "akoflow-${TAG}" source.zip; \
              echo "${TAG}" > /app/AKOFLOW_VERSION

          EXPOSE 8080

          ENTRYPOINT ["/bin/sh", "-c", "exec akoflow-server"]

    - name: Create temporary Docker config dir
      file:
        path: /tmp/docker-config-akoflow
        state: directory
        mode: '0700'

    - name: Create temporary Docker config (disable keychain for non-interactive SSH)
      copy:
        dest: /tmp/docker-config-akoflow/config.json
        content: '{"credsStore":""}'
        mode: '0600'
      vars:
        ansible_become: false

    - name: Build AkôFlow Docker image
      shell: DOCKER_CONFIG=/tmp/docker-config-akoflow docker build -t akoflow-installer /tmp/akoflow-build --no-cache

    - name: Stop existing AkôFlow container (if running)
      shell: docker rm -f akoflow-installer 2>/dev/null || true
      changed_when: false

    - name: Run AkôFlow container
      shell: |
        docker run -d \
          --name akoflow-installer \
          --restart unless-stopped \
          -p {{ akoflow_port }}:8080 \
          -v "{{ akospace_dir | expanduser }}/.env:/app/.env" \
          -v "{{ akospace_dir | expanduser }}/ako.log:/app/ako.log" \
          -v "{{ akospace_dir | expanduser }}/database.db:/storage/database.db" \
          akoflow-installer

    - name: Install kubectl if missing
      shell: |
        if command -v kubectl >/dev/null 2>&1; then
          exit 0
        fi

        OS="$(uname -s | tr '[:upper:]' '[:lower:]')"
        ARCH="$(uname -m)"
        case "$ARCH" in
          x86_64) KARCH="amd64" ;;
          arm64|aarch64) KARCH="arm64" ;;
          *) echo "Unsupported architecture: $ARCH"; exit 1 ;;
        esac

        KUBE_VERSION="$(curl -fsSL https://dl.k8s.io/release/stable.txt)"
        curl -fsSLo /tmp/kubectl "https://dl.k8s.io/release/${KUBE_VERSION}/bin/${OS}/${KARCH}/kubectl"
        chmod +x /tmp/kubectl

        if [ -w /usr/local/bin ]; then
          mv /tmp/kubectl /usr/local/bin/kubectl
        else
          mkdir -p "$HOME/.local/bin"
          mv /tmp/kubectl "$HOME/.local/bin/kubectl"
        fi

    - name: Install kind if missing
      shell: |
        if command -v kind >/dev/null 2>&1; then
          exit 0
        fi

        OS="$(uname -s | tr '[:upper:]' '[:lower:]')"
        ARCH="$(uname -m)"
        case "$ARCH" in
          x86_64) KARCH="amd64" ;;
          arm64|aarch64) KARCH="arm64" ;;
          *) echo "Unsupported architecture: $ARCH"; exit 1 ;;
        esac

        curl -fsSLo /tmp/kind "https://kind.sigs.k8s.io/dl/v0.23.0/kind-${OS}-${KARCH}"
        chmod +x /tmp/kind

        if [ -w /usr/local/bin ]; then
          mv /tmp/kind /usr/local/bin/kind
        else
          mkdir -p "$HOME/.local/bin"
          mv /tmp/kind "$HOME/.local/bin/kind"
        fi

    - name: Create kind cluster
      shell: |
        if kind get clusters 2>/dev/null | grep -qx akoflow-cluster; then
          echo "cluster exists"
        else
          kind create cluster --name akoflow-cluster
        fi
      register: kind_cluster_create
      changed_when: "'cluster exists' not in kind_cluster_create.stdout"

    - name: Check Kubernetes API server endpoint
      shell: kubectl cluster-info --context kind-akoflow-cluster
      register: kind_cluster_info
      changed_when: false

    - name: Extract Kubernetes API port from kind cluster
      shell: |
        echo "{{ kind_cluster_info.stdout }}" \
          | grep 'Kubernetes control plane is running at' \
          | sed -E 's|.*:([0-9]+).*|\1|'
      register: k8s_api_server_port
      changed_when: false
      failed_when: (k8s_api_server_port.stdout | trim) == ''

    - name: Install AkôFlow resources in kind cluster
      shell: >
        kubectl apply
        -f https://raw.githubusercontent.com/UFFeScience/akoflow/main/pkg/server/resource/akoflow-dev-dockerdesktop.yaml
        --context kind-akoflow-cluster
      register: akoflow_apply
      retries: 5
      delay: 15
      until: akoflow_apply.rc == 0

    - name: Wait for akoflow-server-sa service account
      shell: kubectl get serviceaccount akoflow-server-sa --namespace=akoflow --context kind-akoflow-cluster
      register: akoflow_service_account
      changed_when: false
      retries: 30
      delay: 10
      until: akoflow_service_account.rc == 0

    - name: Create token for akoflow-server-sa
      shell: kubectl create token akoflow-server-sa --duration=800h --namespace=akoflow --context kind-akoflow-cluster
      register: akoflow_cluster_token
      changed_when: false
      no_log: true

    - name: Set Kubernetes API host in .env
      lineinfile:
        path: "{{ akospace_dir }}/.env"
        regexp: '^K8S_API_SERVER_HOST='
        line: "K8S_API_SERVER_HOST=host.docker.internal:{{ k8s_api_server_port.stdout | trim }}"
        create: true

    - name: Set Kubernetes API token in .env
      lineinfile:
        path: "{{ akospace_dir }}/.env"
        regexp: '^K8S_API_SERVER_TOKEN='
        line: "K8S_API_SERVER_TOKEN={{ akoflow_cluster_token.stdout | trim }}"
        create: true
      no_log: true

    - name: Set AkôFlow service host in .env
      lineinfile:
        path: "{{ akospace_dir }}/.env"
        regexp: '^AKOFLOW_SERVER_SERVICE_SERVICE_HOST='
        line: 'AKOFLOW_SERVER_SERVICE_SERVICE_HOST=host.docker.internal'
        create: true

    - name: Set AkôFlow service port in .env
      lineinfile:
        path: "{{ akospace_dir }}/.env"
        regexp: '^AKOFLOW_SERVER_SERVICE_SERVICE_PORT='
        line: 'AKOFLOW_SERVER_SERVICE_SERVICE_PORT=8080'
        create: true

    - name: Restart AkôFlow container
      shell: docker restart akoflow-installer

    - name: Write ansible_outputs.json
      copy:
        dest: /tmp/ansible_outputs.json
        content: |
          {
            "akoflow_version": "{{ akoflow_tag.stdout }}",
            "akoflow_url": "http://localhost:{{ akoflow_port }}"
          }
YAML,

            'vars_mapping_json' => [
                'environment_configuration' => [
                    'ssh_user'     => 'ansible_user',
                    'akoflow_port' => 'akoflow_port',
                    'akospace_dir' => 'akospace_dir',
                ],
            ],

            'outputs_mapping_json' => [
                'resources' => [
                    [
                        'name'                  => 'akoflow-host',
                        'ansible_resource_type' => 'akoflow_install',
                        'outputs'               => [
                            'metadata' => [
                                'akoflow_version' => 'akoflow_version',
                                'akoflow_url'     => 'akoflow_url',
                            ],
                        ],
                    ],
                ],
            ],

            'credential_env_keys' => ['SSH_PRIVATE_KEY', 'SSH_PASSWORD'],

            'roles_json' => [],

            'teardown_playbook_yaml' => <<<'YAML'
- name: Teardown AkôFlow on remote host
  hosts: all
  become: false
  environment:
    PATH: "/usr/local/bin:/usr/bin:/bin:/opt/homebrew/bin:{{ ansible_env.HOME }}/.local/bin:{{ ansible_env.PATH | default('') }}"

  tasks:
    - name: Stop AkôFlow container
      shell: docker stop akoflow-installer 2>/dev/null || true
      changed_when: false

    - name: Remove AkôFlow container
      shell: docker rm akoflow-installer 2>/dev/null || true
      changed_when: false

    - name: Remove AkôFlow Docker image
      shell: docker rmi akoflow-installer 2>/dev/null || true
      changed_when: false

    - name: Delete kind cluster
      shell: kind delete cluster --name akoflow-cluster 2>/dev/null || true
      changed_when: false

    - name: Remove temporary build directories
      shell: rm -rf /tmp/akoflow-build /tmp/docker-config-akoflow
      changed_when: false

    - name: Remove akocloud directories created by installer
      shell: rm -rf "{{ ansible_env.HOME }}/akocloud" /akocloud
      changed_when: false
YAML,

            'teardown_vars_mapping_json' => [
                'environment_configuration' => [
                    'ssh_user' => 'ansible_user',
                ],
            ],

            'tasks' => [
                ['name' => 'Check Docker is installed',           'module' => 'shell'],
                ['name' => 'Create akospace directory',           'module' => 'file'],
                ['name' => 'Create empty database file if not present', 'module' => 'file'],
                ['name' => 'Create empty log file if not present', 'module' => 'file'],
                ['name' => 'Create .env file (skip if already exists)', 'module' => 'copy'],
                ['name' => 'Get latest AkôFlow release tag',      'module' => 'shell'],
                ['name' => 'Detect host architecture',            'module' => 'command'],
                ['name' => 'Set binary architecture',             'module' => 'set_fact'],
                ['name' => 'Create Docker build directory',       'module' => 'file'],
                ['name' => 'Write Dockerfile',                    'module' => 'copy'],
                ['name' => 'Create temporary Docker config dir',   'module' => 'file'],
                ['name' => 'Create temporary Docker config (disable keychain for non-interactive SSH)', 'module' => 'copy'],
                ['name' => 'Build AkôFlow Docker image',          'module' => 'shell'],
                ['name' => 'Stop existing AkôFlow container (if running)', 'module' => 'shell'],
                ['name' => 'Run AkôFlow container',               'module' => 'shell'],
                ['name' => 'Install kubectl if missing',          'module' => 'shell'],
                ['name' => 'Install kind if missing',             'module' => 'shell'],
                ['name' => 'Create kind cluster',                 'module' => 'shell'],
                ['name' => 'Check Kubernetes API server endpoint', 'module' => 'shell'],
                ['name' => 'Extract Kubernetes API port from kind cluster', 'module' => 'shell'],
                ['name' => 'Install AkôFlow resources in kind cluster', 'module' => 'shell'],
                ['name' => 'Wait for akoflow-server-sa service account', 'module' => 'shell'],
                ['name' => 'Create token for akoflow-server-sa',  'module' => 'shell'],
                ['name' => 'Set Kubernetes API host in .env',     'module' => 'lineinfile'],
                ['name' => 'Set Kubernetes API token in .env',    'module' => 'lineinfile'],
                ['name' => 'Set AkôFlow service host in .env',    'module' => 'lineinfile'],
                ['name' => 'Set AkôFlow service port in .env',    'module' => 'lineinfile'],
                ['name' => 'Restart AkôFlow container',           'module' => 'shell'],
                ['name' => 'Run AkôFlow installer script again',  'module' => 'shell'],
                ['name' => 'Write ansible_outputs.json',          'module' => 'copy'],
            ],

            'runbooks' => [

                // ── Restart container ─────────────────────────────────────────
                [
                    'name'        => 'Restart AkôFlow',
                    'description' => 'Stops and restarts the akoflow-installer container on the host.',
                    'position'    => 0,
                    'playbook_yaml' => <<<'YAML'
- name: Restart AkôFlow container
  hosts: all
  become: false
  environment:
    PATH: "/usr/local/bin:/usr/bin:/bin:/opt/homebrew/bin:{{ ansible_env.PATH | default('') }}"

  tasks:
    - name: Restart akoflow-installer container
      shell: docker restart akoflow-installer
YAML,
                    'vars_mapping_json'   => ['environment_configuration' => ['ssh_user' => 'ansible_user']],
                    'credential_env_keys' => ['SSH_PRIVATE_KEY', 'SSH_PASSWORD'],
                    'tasks' => [
                        ['name' => 'Restart akoflow-installer container', 'module' => 'shell'],
                    ],
                ],

                // ── Stop container ────────────────────────────────────────────
                [
                    'name'        => 'Stop AkôFlow',
                    'description' => 'Stops the akoflow-installer container.',
                    'position'    => 1,
                    'playbook_yaml' => <<<'YAML'
- name: Stop AkôFlow container
  hosts: all
  become: false
  environment:
    PATH: "/usr/local/bin:/usr/bin:/bin:/opt/homebrew/bin:{{ ansible_env.PATH | default('') }}"

  tasks:
    - name: Stop akoflow-installer container
      shell: docker stop akoflow-installer || true
YAML,
                    'vars_mapping_json'   => ['environment_configuration' => ['ssh_user' => 'ansible_user']],
                    'credential_env_keys' => ['SSH_PRIVATE_KEY', 'SSH_PASSWORD'],
                    'tasks' => [
                        ['name' => 'Stop akoflow-installer container', 'module' => 'shell'],
                    ],
                ],

                // ── View logs ─────────────────────────────────────────────────
                [
                    'name'        => 'View AkôFlow Logs',
                    'description' => 'Tails the last 100 lines of the AkôFlow container logs.',
                    'position'    => 2,
                    'playbook_yaml' => <<<'YAML'
- name: View AkôFlow logs
  hosts: all
  become: false
  environment:
    PATH: "/usr/local/bin:/usr/bin:/bin:/opt/homebrew/bin:{{ ansible_env.PATH | default('') }}"

  tasks:
    - name: Tail container logs
      shell: docker logs --tail=100 akoflow-installer
      register: container_logs
      changed_when: false

    - name: Print logs
      debug:
        msg: "{{ container_logs.stdout_lines }}"
YAML,
                    'vars_mapping_json'   => ['environment_configuration' => ['ssh_user' => 'ansible_user']],
                    'credential_env_keys' => ['SSH_PRIVATE_KEY', 'SSH_PASSWORD'],
                    'tasks' => [
                        ['name' => 'Tail container logs', 'module' => 'shell'],
                        ['name' => 'Print logs',          'module' => 'debug'],
                    ],
                ],

                // ── Update (rebuild + restart) ────────────────────────────────
                [
                    'name'        => 'Update AkôFlow',
                    'description' => 'Downloads the latest AkôFlow release, rebuilds the Docker image and restarts the container.',
                    'position'    => 3,
                    'playbook_yaml' => <<<'YAML'
- name: Update AkôFlow to latest release
  hosts: all
  become: false
  environment:
    PATH: "/usr/local/bin:/usr/bin:/bin:/opt/homebrew/bin:{{ ansible_env.PATH | default('') }}"
  vars:
    akoflow_port: "{{ akoflow_port | default('8080') }}"
    akospace_dir: "{{ akospace_dir | default(ansible_env.HOME + '/akospace') }}"

  tasks:
    - name: Get latest AkôFlow release tag
      shell: >
        curl -fsSL https://api.github.com/repos/UFFeScience/akoflow/releases/latest
        | grep tag_name | cut -d '"' -f 4 | sed 's/^v//'
      register: akoflow_tag
      changed_when: false

    - name: Detect host architecture
      command: uname -m
      register: host_arch
      changed_when: false

    - name: Set binary architecture
      set_fact:
        barch: "{{ 'arm64' if host_arch.stdout in ['aarch64', 'arm64'] else 'amd64' }}"

    - name: Write updated Dockerfile
      copy:
        dest: /tmp/akoflow-build/Dockerfile
        content: |
          FROM debian:bookworm-slim

          RUN apt-get update && apt-get install -y \
              curl \
              ca-certificates \
              unzip \
              sqlite3 \
              ssh \
              sshpass \
              rsync \
           && rm -rf /var/lib/apt/lists/*

          WORKDIR /app

          RUN set -eux; \
              TAG={{ akoflow_tag.stdout }}; \
              BARCH={{ barch }}; \
              curl -fsSL -o /usr/local/bin/akoflow-server \
                "https://github.com/UFFeScience/akoflow/releases/download/v${TAG}/akoflow-server_${TAG}_linux_${BARCH}"; \
              curl -fsSL -o /usr/local/bin/akoflow \
                "https://github.com/UFFeScience/akoflow/releases/download/v${TAG}/akoflow-client_${TAG}_linux_${BARCH}"; \
              chmod +x /usr/local/bin/akoflow-server /usr/local/bin/akoflow; \
              curl -fsSL -o source.zip \
                "https://github.com/UFFeScience/akoflow/archive/refs/tags/v${TAG}.zip"; \
              unzip -qq source.zip "akoflow-${TAG}/pkg/server/engine/httpserver/handlers/akoflow_admin_handler/*"; \
              unzip -qq source.zip "akoflow-${TAG}/pkg/server/scripts/*"; \
              mkdir -p /app/pkg/server/engine/httpserver/handlers; \
              mv "akoflow-${TAG}/pkg/server/engine/httpserver/handlers/akoflow_admin_handler" \
                /app/pkg/server/engine/httpserver/handlers/; \
              mv "akoflow-${TAG}/pkg/server/scripts/" /app/pkg/server/; \
              rm -rf "akoflow-${TAG}" source.zip; \
              echo "${TAG}" > /app/AKOFLOW_VERSION

          EXPOSE 8080

          ENTRYPOINT ["/bin/sh", "-c", "exec akoflow-server"]

    - name: Create temporary Docker config dir
      file:
        path: /tmp/docker-config-akoflow
        state: directory
        mode: '0700'

    - name: Create temporary Docker config (disable keychain)
      copy:
        dest: /tmp/docker-config-akoflow/config.json
        content: '{"credsStore":""}'
        mode: '0600'

    - name: Rebuild AkôFlow Docker image
      shell: DOCKER_CONFIG=/tmp/docker-config-akoflow docker build -t akoflow-installer /tmp/akoflow-build --no-cache

    - name: Stop existing container
      shell: docker rm -f akoflow-installer 2>/dev/null || true
      changed_when: false

    - name: Start updated container
      shell: |
        docker run -d \
          --name akoflow-installer \
          --restart unless-stopped \
          -p {{ akoflow_port }}:8080 \
          -v "{{ akospace_dir | expanduser }}/.env:/app/.env" \
          -v "{{ akospace_dir | expanduser }}/ako.log:/app/ako.log" \
          -v "{{ akospace_dir | expanduser }}/database.db:/storage/database.db" \
          akoflow-installer
YAML,
                    'vars_mapping_json' => [
                        'environment_configuration' => [
                            'ssh_user'     => 'ansible_user',
                            'akoflow_port' => 'akoflow_port',
                            'akospace_dir' => 'akospace_dir',
                        ],
                    ],
                    'credential_env_keys' => ['SSH_PRIVATE_KEY', 'SSH_PASSWORD'],
                    'tasks' => [
                        ['name' => 'Get latest AkôFlow release tag',  'module' => 'shell'],
                        ['name' => 'Detect host architecture',         'module' => 'command'],
                        ['name' => 'Set binary architecture',          'module' => 'set_fact'],
                        ['name' => 'Write updated Dockerfile',         'module' => 'copy'],
                        ['name' => 'Create temporary Docker config dir', 'module' => 'file'],
                        ['name' => 'Create temporary Docker config (disable keychain)', 'module' => 'copy'],
                        ['name' => 'Rebuild AkôFlow Docker image',     'module' => 'shell'],
                        ['name' => 'Stop existing container',          'module' => 'shell'],
                        ['name' => 'Start updated container',          'module' => 'shell'],
                    ],
                ],
            ],
        ];
    }
}
