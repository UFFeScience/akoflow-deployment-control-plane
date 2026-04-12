<?php

namespace App\Services;

use App\Contracts\HasRunLog;
use RuntimeException;

/**
 * Low-level Ansible executor.
 *
 * Runs `ansible-galaxy install` (when requirements.yml is present) followed by
 * `ansible-playbook`, streaming every output line into the activity run log in
 * real time.
 *
 * SSH credentials are injected as process environment variables — the private
 * key content is written to a temporary file (via tempnam) which is deleted
 * immediately after the process finishes. The file path is exposed to the
 * ansible process only via the ANSIBLE_PRIVATE_KEY_FILE env var.
 */
class AnsibleProcessRunnerService
{
    /**
     * Run ansible-galaxy + ansible-playbook inside the given workspace directory.
     *
     * @param  string                 $workspacePath  Absolute path to the Ansible workspace.
     * @param  array<string, string>  $credentialEnv  Provider credentials to inject as env vars.
    * @param  HasRunLog              $run            Log target; updated in real time.
    * @param  callable(string):void|null $onLogLine Optional callback for each streamed log line.
    * @return int  The ansible-playbook exit code (0 = success).
     *
     * @throws RuntimeException when the process cannot be started.
     */
    public function run(string $workspacePath, array $credentialEnv, HasRunLog $run, ?callable $onLogLine = null): int
    {
        $tempKeyFile = null;
        $env         = $this->buildProcessEnv($credentialEnv, $workspacePath, $tempKeyFile);

        try {
            if (file_exists($workspacePath . '/requirements.yml')) {
                $this->installRoles($workspacePath, $env, $run, $onLogLine);
            }

            return $this->runPlaybook($workspacePath, $env, $run, $onLogLine);
        } finally {
            if ($tempKeyFile !== null && file_exists($tempKeyFile)) {
                unlink($tempKeyFile);
            }
        }
    }

    /**
     * Reads ansible_outputs.json written by the playbook and returns the decoded map.
     *
     * The playbook is responsible for writing this file. Expected format:
     *   { "output_name": "value", ... }   (flat key-value, not the Terraform nested format)
     *
     * @return array<string, mixed>|null
     */
    public function captureOutputs(string $workspacePath, HasRunLog $run): ?array
    {
        $outputFile = $workspacePath . '/ansible_outputs.json';

        if (!file_exists($outputFile)) {
            $run->appendLog('[akocloud] ansible_outputs.json not found — outputs not captured.');
            return null;
        }

        $json    = file_get_contents($outputFile);
        $decoded = json_decode($json, true);

        if (!is_array($decoded)) {
            $run->appendLog('[akocloud] ansible_outputs.json is not valid JSON — outputs not captured.');
            return null;
        }

        $run->appendLog('[akocloud] Captured ' . count($decoded) . ' output(s) from ansible_outputs.json.');

        return $decoded;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Internal helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Builds the process environment array.
     *
     * If $credentialEnv contains SSH_PRIVATE_KEY, its content is written to a
     * temp file and ANSIBLE_PRIVATE_KEY_FILE is set to that path instead.
     * $tempKeyFile is set by reference so the caller can delete it after use.
     */
    private function buildProcessEnv(array $credentialEnv, string $workspacePath, ?string &$tempKeyFile): array
    {
        $env = array_merge($_SERVER, $_ENV, [
            'ANSIBLE_FORCE_COLOR'         => '0',
            'ANSIBLE_HOST_KEY_CHECKING'   => 'False',
            'ANSIBLE_STDOUT_CALLBACK'     => 'default',
            'PYTHONUNBUFFERED'            => '1',
        ]);

        // Strip irrelevant PHP superglobals that cause env pollution
        unset($env['argv'], $env['argc']);

        if (isset($credentialEnv['SSH_PRIVATE_KEY'])) {
            $tempDir = storage_path('app/ansible/tmp');

            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $tempKeyFile = tempnam($tempDir, 'ako_ssh_');
            chmod($tempKeyFile, 0600);

            // Normalize the key: replace literal \n escape sequences with real newlines
            // (keys stored via API/JSON often arrive with \\n instead of actual line breaks),
            // then ensure a single trailing newline — libcrypto requires a well-formed PEM file.
            $keyContent = $credentialEnv['SSH_PRIVATE_KEY'];
            $keyContent = str_replace(['\r\n', '\n', '\r'], "\n", $keyContent);
            $keyContent = rtrim($keyContent) . "\n";

            file_put_contents($tempKeyFile, $keyContent);

            $env['ANSIBLE_PRIVATE_KEY_FILE'] = $tempKeyFile;
            unset($credentialEnv['SSH_PRIVATE_KEY']);
        }

        if (isset($credentialEnv['SSH_PASSWORD']) && $credentialEnv['SSH_PASSWORD'] !== '') {
            // Inject ansible_ssh_pass into extra_vars.json so Ansible uses password auth.
            $this->injectExtraVar($workspacePath, 'ansible_ssh_pass', $credentialEnv['SSH_PASSWORD']);
            unset($credentialEnv['SSH_PASSWORD']);
        }

        foreach ($credentialEnv as $key => $value) {
            $env[$key] = $value;
        }

        return $env;
    }

    private function installRoles(string $workspacePath, array $env, HasRunLog $run, ?callable $onLogLine = null): void
    {
        $run->appendLog('[akocloud] Installing Ansible roles from requirements.yml...');

        $exitCode = $this->streamProcess(
            'ansible-galaxy install -r requirements.yml',
            $workspacePath,
            $env,
            $run,
            $onLogLine,
        );

        if ($exitCode !== 0) {
            throw new RuntimeException(
                "ansible-galaxy install failed with exit code {$exitCode}."
            );
        }
    }

    private function runPlaybook(string $workspacePath, array $env, HasRunLog $run, ?callable $onLogLine = null): int
    {
        $run->appendLog('[akocloud] Running ansible-playbook...');

        return $this->streamProcess(
            'ansible-playbook -i inventory.ini playbook.yml --extra-vars @extra_vars.json',
            $workspacePath,
            $env,
            $run,
            $onLogLine,
        );
    }



    /**
     * Merges a single key-value pair into extra_vars.json.
     * Called before the Ansible process starts to inject credential-derived vars
     * (e.g. ansible_ssh_pass) that cannot be set via process-level env vars.
     */
    private function injectExtraVar(string $workspacePath, string $key, string $value): void
    {
        $extraVarsFile = $workspacePath . '/extra_vars.json';
        $data = file_exists($extraVarsFile)
            ? (json_decode((string) file_get_contents($extraVarsFile), true) ?? [])
            : [];

        $data[$key] = $value;

        file_put_contents(
            $extraVarsFile,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        );
    }

    /**
     * Opens a process, streams stdout+stderr line by line to the run log,
     * and returns the exit code.
     */
    private function streamProcess(string $command, string $cwd, array $env, HasRunLog $run, ?callable $onLogLine = null): int
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes, $cwd, $env);

        if (!is_resource($process)) {
            throw new RuntimeException("Failed to start process: {$command}");
        }

        fclose($pipes[0]);

        // Merge stdout and stderr into a single stream for real-time logging
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        while (true) {
            $status = proc_get_status($process);

            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);

            foreach ([$stdout, $stderr] as $chunk) {
                if (!empty($chunk)) {
                    foreach (explode("\n", rtrim($chunk)) as $line) {
                        if ($line !== '') {
                            $run->appendLog($line);
                            if ($onLogLine !== null) {
                                $onLogLine($line);
                            }
                        }
                    }
                }
            }

            if (!$status['running']) {
                break;
            }

            usleep(50000); // 50ms poll
        }

        fclose($pipes[1]);
        fclose($pipes[2]);

        return proc_close($process);
    }
}
