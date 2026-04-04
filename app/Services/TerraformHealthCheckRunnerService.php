<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Runs a minimal Terraform workspace (built by a cloud-specific health check service)
 * to verify cloud provider connectivity.
 *
 * Terraform is executed directly (installed in the container) and uses its providers
 * to call cloud REST APIs — no cloud-vendor CLI tools are invoked from PHP.
 *
 * Workspace layout under storage/app/terraform/health-check/{providerId}-{uniqid}/:
 *   main.tf                – provider + data source or null_resource
 *   terraform.tfvars.json  – variables (only for providers that need them, e.g. Slurm)
 *
 * Credentials for AWS and GCP are injected as environment variables directly into the
 * Terraform process — they are never written to disk.
 */
class TerraformHealthCheckRunnerService
{
    private const BASE_DIR  = 'terraform/health-check';
    private const CACHE_DIR = 'terraform/.plugin-cache';

    /**
     * @param  string                         $providerId
     * @param  array{main_tf: string, env: array<string,string>, tfvars: array}  $workspace
     * @return array{healthy: bool, message: string}
     */
    public function run(string $providerId, array $workspace): array
    {
        $workspaceDir = storage_path(
            'app/' . self::BASE_DIR . '/' . $providerId . '-' . uniqid('', true),
        );

        try {
            $this->writeWorkspaceFiles($workspaceDir, $workspace);

            return $this->executeCheck($workspaceDir, $workspace['env'] ?? []);
        } catch (\Throwable $e) {
            Log::error('TerraformHealthCheckRunnerService: unexpected error', [
                'provider_id' => $providerId,
                'error'       => $e->getMessage(),
            ]);

            return ['healthy' => false, 'message' => 'Internal error during health check: ' . $e->getMessage()];
        } finally {
            $this->deleteDirectory($workspaceDir);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Workspace file writing
    // ─────────────────────────────────────────────────────────────────────────

    private function writeWorkspaceFiles(string $dir, array $workspace): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($dir . '/main.tf', $workspace['main_tf']);

        if (!empty($workspace['tfvars'])) {
            file_put_contents(
                $dir . '/terraform.tfvars.json',
                json_encode($workspace['tfvars'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            );
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Terraform execution
    // ─────────────────────────────────────────────────────────────────────────

    private function executeCheck(string $workspaceDir, array $credentialEnv): array
    {
        $env = $this->buildProcessEnv($credentialEnv);

        // Step 1: terraform init (downloads providers into plugin cache)
        [$initLog, $initCode] = $this->runTerraformCommand(
            ['terraform', 'init', '-no-color', '-input=false'],
            $workspaceDir,
            $env,
        );

        if ($initCode !== 0) {
            return [
                'healthy' => false,
                'message' => 'Terraform init failed: ' . $this->extractError($initLog),
            ];
        }

        // Step 2: terraform apply
        // - AWS/GCP: data sources are read during apply (REST API calls via Terraform provider)
        // - Slurm: null_resource remote-exec provisioner opens the SSH connection inside Terraform
        $applyArgs = ['terraform', 'apply', '-auto-approve', '-no-color', '-input=false'];

        if (file_exists($workspaceDir . '/terraform.tfvars.json')) {
            $applyArgs[] = '-var-file=terraform.tfvars.json';
        }

        [$applyLog, $applyCode] = $this->runTerraformCommand($applyArgs, $workspaceDir, $env);

        if ($applyCode === 0) {
            return [
                'healthy' => true,
                'message' => 'Cloud provider connectivity verified via Terraform.',
            ];
        }

        return [
            'healthy' => false,
            'message' => 'Terraform health check failed: ' . $this->extractError($applyLog),
        ];
    }

    /**
     * Build the environment for the Terraform process.
     * Merges credential env vars over the current process environment, plus Terraform-specific vars.
     *
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
            'PATH'                 => getenv('PATH') ?: '/usr/local/bin:/usr/bin:/bin',
            'HOME'                 => getenv('HOME') ?: '/tmp',
            'TF_IN_AUTOMATION'     => '1',
            'TF_INPUT'             => '0',
            'TF_PLUGIN_CACHE_DIR'  => $cacheDir,
        ];

        // Credential env vars take precedence over base and override any ambient cloud credentials
        return array_merge($base, $credentialEnv);
    }

    /**
     * Runs a Terraform command in the given directory with the specified environment.
     *
     * @param  string[]             $cmd
     * @param  array<string,string> $env
     * @return array{0: string, 1: int}  [combined output, exit code]
     */
    private function runTerraformCommand(array $cmd, string $cwd, array $env): array
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes, $cwd, $env);

        if (!is_resource($process)) {
            throw new RuntimeException('Failed to start Terraform process. Ensure terraform is installed in the container.');
        }

        fclose($pipes[0]);
        $stdout   = stream_get_contents($pipes[1]);
        $stderr   = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        return [$stdout . "\n" . $stderr, $exitCode];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Output parsing
    // ─────────────────────────────────────────────────────────────────────────

    private function extractError(string $log): string
    {
        $lines  = explode("\n", $log);
        $errors = [];

        foreach ($lines as $line) {
            $trimmed = trim(ltrim($line, '│ '));

            if ($trimmed === '') {
                continue;
            }

            if (
                str_starts_with($trimmed, 'Error:')
                || str_starts_with($trimmed, 'error:')
                || str_contains($trimmed, 'InvalidClientTokenId')
                || str_contains($trimmed, 'AuthFailure')
                || str_contains($trimmed, 'AccessDenied')
                || str_contains($trimmed, 'invalid_grant')
                || str_contains($trimmed, 'Connection refused')
                || str_contains($trimmed, 'No such host')
            ) {
                $errors[] = $trimmed;
            }
        }

        if (!empty($errors)) {
            return implode(' ', array_slice($errors, 0, 3));
        }

        return 'Check provider credentials and connectivity.';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Cleanup
    // ─────────────────────────────────────────────────────────────────────────

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (array_diff(scandir($dir), ['.', '..']) as $item) {
            $path = $dir . '/' . $item;
            // Check symlink first: is_dir() follows symlinks and returns true for
            // symlinks pointing at directories, but rmdir() cannot remove a symlink.
            if (is_link($path) || is_file($path)) {
                unlink($path);
            } else {
                $this->deleteDirectory($path);
            }
        }

        rmdir($dir);
    }
}
