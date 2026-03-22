<?php

namespace App\Services;

use App\Models\TerraformRun;
use RuntimeException;

/**
 * Low-level Terraform executor.
 *
 * Runs `terraform init` followed by `terraform apply` or `terraform destroy`
 * inside the given workspace directory, streaming every output line into the
 * TerraformRun log in real time.
 *
 * Provider credentials are injected directly into the process environment —
 * they are never written to disk.
 */
class TerraformProcessRunnerService
{
    private const CACHE_DIR = 'terraform/.plugin-cache';

    /**
     * @param  string                 $workspacePath  Absolute path to the Terraform workspace.
     * @param  string                 $action         'apply' or 'destroy'.
     * @param  array<string, string>  $credentialEnv  Provider credentials to inject as env vars.
     * @param  TerraformRun           $run            Log target; updated in real time.
     * @return int  The Terraform exit code (0 = success).
     *
     * @throws RuntimeException when init fails or the process cannot be started.
     */
    public function run(string $workspacePath, string $action, array $credentialEnv, TerraformRun $run): int
    {
        $env = $this->buildProcessEnv($credentialEnv);

        $this->terraformInit($workspacePath, $env, $run);

        return $this->terraformAction($workspacePath, $action, $env, $run);
    }

    /**
     * Runs `terraform output -json` and returns the decoded map.
     *
     * The format returned by Terraform is:
     *   { "output_name": { "value": "...", "type": "string" }, ... }
     *
     * @return array<string, array{value: mixed, type: string}>|null
     */
    public function captureOutputs(string $workspacePath, array $credentialEnv, TerraformRun $run): ?array
    {
        $run->appendLog('[akocloud] Capturing terraform outputs...');

        $env     = $this->buildProcessEnv($credentialEnv);
        $process = proc_open(
            'terraform output -json',
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $workspacePath,
            $env,
        );

        if (!is_resource($process)) {
            $run->appendLog('[akocloud] Failed to start terraform output -json process.');
            return null;
        }

        fclose($pipes[0]);
        $stdout   = stream_get_contents($pipes[1]);
        $stderr   = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            $run->appendLog('[akocloud] terraform output -json failed — outputs not captured.');
            if (!empty(trim($stderr))) {
                $run->appendLog(trim($stderr));
            }
            return null;
        }

        $decoded = json_decode($stdout, true);

        if (!is_array($decoded)) {
            $run->appendLog('[akocloud] terraform output -json returned invalid JSON.');
            return null;
        }

        return $decoded;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Terraform steps
    // ─────────────────────────────────────────────────────────────────────────

    private function terraformInit(string $workspacePath, array $env, TerraformRun $run): void
    {
        $run->appendLog('[akocloud] Running: terraform init');

        $result = $this->execute(
            ['terraform', 'init', '-no-color', '-input=false'],
            $workspacePath,
            $env,
            $run,
        );

        if ((int) $result[1] !== 0) {
            throw new RuntimeException("Terraform init failed (exit code {$result[1]}).");
        }
    }

    private function terraformAction(string $workspacePath, string $action, array $env, TerraformRun $run): int
    {
        $cmd = ['terraform', $action, '-auto-approve', '-no-color', '-input=false'];

        if (file_exists($workspacePath . '/terraform.tfvars.json')) {
            $cmd[] = '-var-file=terraform.tfvars.json';
        }

        $run->appendLog("[akocloud] Running: terraform {$action}");

        $result = $this->execute($cmd, $workspacePath, $env, $run);

        return (int) $result[1];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Process execution
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @param  string[]             $cmd
     * @param  array<string,string> $env
     * @return array{string, int}       [combined output, exit code]
     */
    private function execute(array $cmd, string $cwd, array $env, TerraformRun $run): array
    {
        // Build a shell string so the command is resolved via /bin/sh,
        // which correctly honours the PATH we pass in $env.
        $shellCmd = implode(' ', array_map('escapeshellarg', $cmd));

        $run->appendLog("[akocloud][cmd] {$shellCmd}");

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($shellCmd, $descriptors, $pipes, $cwd, $env);

        if (!is_resource($process)) {
            throw new RuntimeException(
                'Failed to start Terraform process. Ensure terraform is installed in the container.'
            );
        }

        fclose($pipes[0]);

        $output = '';
        while (!feof($pipes[1])) {
            $line = fgets($pipes[1], 4096);
            if ($line !== false) {
                $run->appendLog(rtrim($line));
                $output .= $line;
            }
        }

        $stderr = stream_get_contents($pipes[2]);
        if (!empty(trim($stderr))) {
            foreach (explode("\n", $stderr) as $line) {
                if (trim($line) !== '') {
                    $run->appendLog(rtrim($line));
                }
            }
            $output .= $stderr;
        }

        fclose($pipes[1]);
        fclose($pipes[2]);

        return [$output, proc_close($process)];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Environment building
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @param  array<string, string>  $credentialEnv
     * @return array<string, string>
     */
    private function buildProcessEnv(array $credentialEnv): array
    {
        $cacheDir = storage_path('app/' . self::CACHE_DIR);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $base = [
            'PATH'                => getenv('PATH') ?: '/usr/local/bin:/usr/bin:/bin',
            'HOME'                => getenv('HOME') ?: '/tmp',
            'TF_IN_AUTOMATION'    => '1',
            'TF_INPUT'            => '0',
            'TF_PLUGIN_CACHE_DIR' => $cacheDir,
        ];

        // Credentials take precedence, overriding any ambient values
        return array_merge($base, $credentialEnv);
    }
}
