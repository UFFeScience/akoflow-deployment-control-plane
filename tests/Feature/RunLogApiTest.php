<?php

namespace Tests\Feature;

use App\Models\Environment;
use App\Models\Organization;
use App\Models\Project;
use App\Models\RunLog;
use App\Models\TerraformRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for the run_logs table, RunLog model, RunLogRepository,
 * and all API endpoints that expose log data.
 */
class RunLogApiTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────────────────────────────────────
    // helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function authHeader(User $user): array
    {
        return ['Authorization' => 'Bearer ' . $user->createToken('api')->plainTextToken];
    }

    private function makeRun(?Environment $environment = null, string $status = TerraformRun::STATUS_APPLIED): array
    {
        $user        = User::factory()->create();
        $org         = Organization::factory()->create(['user_id' => $user->id]);
        $project     = Project::factory()->create(['organization_id' => $org->id]);
        $environment ??= Environment::create([
            'project_id' => $project->id,
            'name'       => 'env-' . uniqid(),
            'status'     => Environment::STATUSES[0],
        ]);
        $run = TerraformRun::create([
            'environment_id' => $environment->id,
            'status'         => $status,
            'action'         => TerraformRun::ACTION_APPLY,
        ]);

        return compact('user', 'project', 'environment', 'run');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RunLog resource shape
    // ─────────────────────────────────────────────────────────────────────────

    public function test_log_resource_returns_expected_fields(): void
    {
        ['user' => $user, 'project' => $project, 'environment' => $environment, 'run' => $run] = $this->makeRun();

        RunLog::create([
            'terraform_run_id' => $run->id,
            'environment_id'   => $environment->id,
            'source'           => RunLog::SOURCE_TERRAFORM,
            'level'            => RunLog::LEVEL_INFO,
            'message'          => 'Initializing plugins',
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/projects/{$project->id}/environments/{$environment->id}/terraform-runs/{$run->id}/logs");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'terraform_run_id',
                        'provisioned_resource_id',
                        'environment_id',
                        'source',
                        'level',
                        'message',
                        'created_at',
                    ],
                ],
            ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TerraformRun::appendLog — writes to run_logs, not logs column
    // ─────────────────────────────────────────────────────────────────────────

    public function test_append_log_creates_run_log_row(): void
    {
        ['run' => $run, 'environment' => $environment] = $this->makeRun();

        $run->appendLog('[akocloud] Running: terraform init');

        $this->assertDatabaseHas('run_logs', [
            'terraform_run_id' => $run->id,
            'environment_id'   => $environment->id,
            'source'           => RunLog::SOURCE_TERRAFORM,
            'message'          => '[akocloud] Running: terraform init',
        ]);
    }

    public function test_append_log_infers_level_info_for_plain_lines(): void
    {
        ['run' => $run] = $this->makeRun();

        $run->appendLog('Initializing provider plugins...');

        $this->assertDatabaseHas('run_logs', [
            'terraform_run_id' => $run->id,
            'level'            => RunLog::LEVEL_INFO,
        ]);
    }

    public function test_append_log_infers_level_error(): void
    {
        ['run' => $run] = $this->makeRun();

        $run->appendLog('[error] failed to connect to provider');

        $this->assertDatabaseHas('run_logs', [
            'terraform_run_id' => $run->id,
            'level'            => RunLog::LEVEL_ERROR,
        ]);
    }

    public function test_append_log_infers_level_warn(): void
    {
        ['run' => $run] = $this->makeRun();

        $run->appendLog('[warning] deprecated resource type used');

        $this->assertDatabaseHas('run_logs', [
            'terraform_run_id' => $run->id,
            'level'            => RunLog::LEVEL_WARN,
        ]);
    }

    public function test_append_log_multiple_lines_each_get_own_row(): void
    {
        ['run' => $run] = $this->makeRun();

        $run->appendLog('line one');
        $run->appendLog('line two');
        $run->appendLog('line three');

        $this->assertDatabaseCount('run_logs', 3);
        $this->assertSame(3, RunLog::where('terraform_run_id', $run->id)->count());
    }

    public function test_terraform_run_no_longer_has_logs_column(): void
    {
        ['run' => $run] = $this->makeRun();

        // The column was dropped — the model should not include it in fillable/casts
        $this->assertArrayNotHasKey('logs', $run->getAttributes());
        $this->assertArrayNotHasKey('logs', $run->getFillable() ? array_flip($run->getFillable()) : []);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Incremental polling — after_id
    // ─────────────────────────────────────────────────────────────────────────

    public function test_after_id_excludes_the_given_id_itself(): void
    {
        ['user' => $user, 'project' => $project, 'environment' => $environment, 'run' => $run] = $this->makeRun();

        $log = RunLog::create(['terraform_run_id' => $run->id, 'environment_id' => $environment->id, 'source' => RunLog::SOURCE_TERRAFORM, 'level' => RunLog::LEVEL_INFO, 'message' => 'anchor']);
        RunLog::create(['terraform_run_id' => $run->id, 'environment_id' => $environment->id, 'source' => RunLog::SOURCE_TERRAFORM, 'level' => RunLog::LEVEL_INFO, 'message' => 'newer']);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/projects/{$project->id}/environments/{$environment->id}/terraform-runs/{$run->id}/logs?after_id={$log->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.message', 'newer');
    }

    public function test_after_id_returns_empty_when_no_newer_entries(): void
    {
        ['user' => $user, 'project' => $project, 'environment' => $environment, 'run' => $run] = $this->makeRun();

        $log = RunLog::create(['terraform_run_id' => $run->id, 'environment_id' => $environment->id, 'source' => RunLog::SOURCE_TERRAFORM, 'level' => RunLog::LEVEL_INFO, 'message' => 'last']);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/projects/{$project->id}/environments/{$environment->id}/terraform-runs/{$run->id}/logs?after_id={$log->id}");

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // logs are scoped to a single run
    // ─────────────────────────────────────────────────────────────────────────

    public function test_logs_from_different_runs_are_isolated(): void
    {
        ['user' => $user, 'project' => $project, 'environment' => $environment, 'run' => $runA] = $this->makeRun();
        $runB = TerraformRun::create([
            'environment_id' => $environment->id,
            'status'         => TerraformRun::STATUS_APPLIED,
            'action'         => TerraformRun::ACTION_APPLY,
        ]);

        RunLog::create(['terraform_run_id' => $runA->id, 'environment_id' => $environment->id, 'source' => RunLog::SOURCE_TERRAFORM, 'level' => RunLog::LEVEL_INFO, 'message' => 'run A log']);
        RunLog::create(['terraform_run_id' => $runB->id, 'environment_id' => $environment->id, 'source' => RunLog::SOURCE_TERRAFORM, 'level' => RunLog::LEVEL_INFO, 'message' => 'run B log']);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/projects/{$project->id}/environments/{$environment->id}/terraform-runs/{$runA->id}/logs");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.message', 'run A log');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // environment_id is denormalized correctly
    // ─────────────────────────────────────────────────────────────────────────

    public function test_run_log_environment_id_matches_run_environment(): void
    {
        ['run' => $run, 'environment' => $environment] = $this->makeRun();

        $run->appendLog('check env_id');

        $log = RunLog::where('terraform_run_id', $run->id)->firstOrFail();

        $this->assertEquals($environment->id, $log->environment_id);
    }
}
