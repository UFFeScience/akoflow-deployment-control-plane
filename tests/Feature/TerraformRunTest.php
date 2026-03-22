<?php

namespace Tests\Feature;

use App\Jobs\DestroyEnvironmentJob;
use App\Jobs\ProvisionEnvironmentJob;
use App\Models\Environment;
use App\Models\Organization;
use App\Models\Project;
use App\Models\RunLog;
use App\Models\TerraformRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TerraformRunTest extends TestCase
{
    use RefreshDatabase;

    private function authHeader(User $user): array
    {
        $token = $user->createToken('api-token')->plainTextToken;

        return ['Authorization' => "Bearer $token"];
    }

    private function projectBelongingToUser(User $user): Project
    {
        $org = Organization::factory()->create(['user_id' => $user->id]);

        return Project::factory()->create(['organization_id' => $org->id]);
    }

    private function environmentInProject(Project $project): Environment
    {
        return Environment::create([
            'project_id' => $project->id,
            'name'       => 'Test Environment',
            'status'     => Environment::STATUSES[0],
        ]);
    }

    private function terraformRunForEnvironment(Environment $environment, string $status = TerraformRun::STATUS_APPLIED): TerraformRun
    {
        return TerraformRun::create([
            'environment_id' => $environment->id,
            'status'        => $status,
            'action'        => TerraformRun::ACTION_APPLY,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // index
    // ─────────────────────────────────────────────────────────────────────────

    public function test_user_can_list_terraform_runs_for_environment(): void
    {
        $user       = User::factory()->create();
        $project    = $this->projectBelongingToUser($user);
        $environment = $this->environmentInProject($project);

        $this->terraformRunForEnvironment($environment, TerraformRun::STATUS_APPLIED);
        $this->terraformRunForEnvironment($environment, TerraformRun::STATUS_FAILED);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/projects/{$project->id}/environments/{$environment->id}/terraform-runs");

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    public function test_user_cannot_list_terraform_runs_of_another_users_project(): void
    {
        $userA      = User::factory()->create();
        $userB      = User::factory()->create();
        $project    = $this->projectBelongingToUser($userB);
        $environment = $this->environmentInProject($project);

        $response = $this->withHeaders($this->authHeader($userA))
            ->getJson("/api/projects/{$project->id}/environments/{$environment->id}/terraform-runs");

        $response->assertStatus(403);
    }

    public function test_list_terraform_runs_returns_404_when_environment_not_found(): void
    {
        $user    = User::factory()->create();
        $project = $this->projectBelongingToUser($user);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/projects/{$project->id}/environments/99999/terraform-runs");

        $response->assertStatus(404);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // show
    // ─────────────────────────────────────────────────────────────────────────

    public function test_user_can_get_terraform_run_by_id(): void
    {
        $user       = User::factory()->create();
        $project    = $this->projectBelongingToUser($user);
        $environment = $this->environmentInProject($project);
        $run        = $this->terraformRunForEnvironment($environment);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/projects/{$project->id}/environments/{$environment->id}/terraform-runs/{$run->id}");

        $response->assertStatus(200)
            ->assertJsonPath('id', $run->id)
            ->assertJsonPath('status', TerraformRun::STATUS_APPLIED);
    }

    public function test_show_terraform_run_returns_404_when_run_not_found(): void
    {
        $user       = User::factory()->create();
        $project    = $this->projectBelongingToUser($user);
        $environment = $this->environmentInProject($project);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/projects/{$project->id}/environments/{$environment->id}/terraform-runs/99999");

        $response->assertStatus(404);
    }

    public function test_show_terraform_run_returns_404_when_run_belongs_to_different_environment(): void
    {
        $user        = User::factory()->create();
        $project     = $this->projectBelongingToUser($user);
        $environmentA = $this->environmentInProject($project);
        $environmentB = $this->environmentInProject($project);
        $run         = $this->terraformRunForEnvironment($environmentA);

        // Try to access environmentA's run via environmentB's URL
        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/projects/{$project->id}/environments/{$environmentB->id}/terraform-runs/{$run->id}");

        $response->assertStatus(404);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // store (provision)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_user_can_trigger_provisioning(): void
    {
        Queue::fake();

        $user       = User::factory()->create();
        $project    = $this->projectBelongingToUser($user);
        $environment = $this->environmentInProject($project);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson("/api/projects/{$project->id}/environments/{$environment->id}/terraform-runs");

        $response->assertStatus(202)
            ->assertJsonPath('message', 'Provisioning job queued.');

        Queue::assertPushed(ProvisionEnvironmentJob::class);
    }

    public function test_user_cannot_trigger_provisioning_on_another_users_environment(): void
    {
        Queue::fake();

        $userA      = User::factory()->create();
        $userB      = User::factory()->create();
        $project    = $this->projectBelongingToUser($userB);
        $environment = $this->environmentInProject($project);

        $response = $this->withHeaders($this->authHeader($userA))
            ->postJson("/api/projects/{$project->id}/environments/{$environment->id}/terraform-runs");

        $response->assertStatus(403);

        Queue::assertNotPushed(ProvisionEnvironmentJob::class);
    }

    public function test_provision_returns_404_when_environment_not_found(): void
    {
        Queue::fake();

        $user    = User::factory()->create();
        $project = $this->projectBelongingToUser($user);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson("/api/projects/{$project->id}/environments/99999/terraform-runs");

        $response->assertStatus(404);

        Queue::assertNotPushed(ProvisionEnvironmentJob::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // destroy
    // ─────────────────────────────────────────────────────────────────────────

    public function test_user_can_trigger_destroy(): void
    {
        Queue::fake();

        $user       = User::factory()->create();
        $project    = $this->projectBelongingToUser($user);
        $environment = $this->environmentInProject($project);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson("/api/projects/{$project->id}/environments/{$environment->id}/terraform-runs/destroy");

        $response->assertStatus(202)
            ->assertJsonPath('message', 'Destroy job queued.');

        Queue::assertPushed(DestroyEnvironmentJob::class);
    }

    public function test_user_cannot_trigger_destroy_on_another_users_environment(): void
    {
        Queue::fake();

        $userA      = User::factory()->create();
        $userB      = User::factory()->create();
        $project    = $this->projectBelongingToUser($userB);
        $environment = $this->environmentInProject($project);

        $response = $this->withHeaders($this->authHeader($userA))
            ->postJson("/api/projects/{$project->id}/environments/{$environment->id}/terraform-runs/destroy");

        $response->assertStatus(403);

        Queue::assertNotPushed(DestroyEnvironmentJob::class);
    }

    public function test_destroy_returns_404_when_environment_not_found(): void
    {
        Queue::fake();

        $user    = User::factory()->create();
        $project = $this->projectBelongingToUser($user);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson("/api/projects/{$project->id}/environments/99999/terraform-runs/destroy");

        $response->assertStatus(404);

        Queue::assertNotPushed(DestroyEnvironmentJob::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // logs
    // ─────────────────────────────────────────────────────────────────────────

    public function test_user_can_list_logs_for_terraform_run(): void
    {
        $user        = User::factory()->create();
        $project     = $this->projectBelongingToUser($user);
        $environment = $this->environmentInProject($project);
        $run         = $this->terraformRunForEnvironment($environment);

        RunLog::create([
            'terraform_run_id' => $run->id,
            'environment_id'   => $environment->id,
            'source'           => RunLog::SOURCE_TERRAFORM,
            'level'            => RunLog::LEVEL_INFO,
            'message'          => '[akocloud] Running: terraform init',
        ]);
        RunLog::create([
            'terraform_run_id' => $run->id,
            'environment_id'   => $environment->id,
            'source'           => RunLog::SOURCE_TERRAFORM,
            'level'            => RunLog::LEVEL_ERROR,
            'message'          => '[error] Something failed',
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/projects/{$project->id}/environments/{$environment->id}/terraform-runs/{$run->id}/logs");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.source', RunLog::SOURCE_TERRAFORM)
            ->assertJsonPath('data.0.level', RunLog::LEVEL_INFO)
            ->assertJsonPath('data.0.terraform_run_id', $run->id);
    }

    public function test_logs_endpoint_returns_entries_ordered_by_id_ascending(): void
    {
        $user        = User::factory()->create();
        $project     = $this->projectBelongingToUser($user);
        $environment = $this->environmentInProject($project);
        $run         = $this->terraformRunForEnvironment($environment);

        RunLog::create(['terraform_run_id' => $run->id, 'environment_id' => $environment->id, 'source' => RunLog::SOURCE_TERRAFORM, 'level' => RunLog::LEVEL_INFO, 'message' => 'first']);
        RunLog::create(['terraform_run_id' => $run->id, 'environment_id' => $environment->id, 'source' => RunLog::SOURCE_TERRAFORM, 'level' => RunLog::LEVEL_INFO, 'message' => 'second']);
        RunLog::create(['terraform_run_id' => $run->id, 'environment_id' => $environment->id, 'source' => RunLog::SOURCE_TERRAFORM, 'level' => RunLog::LEVEL_INFO, 'message' => 'third']);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/projects/{$project->id}/environments/{$environment->id}/terraform-runs/{$run->id}/logs");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.message', 'first')
            ->assertJsonPath('data.2.message', 'third');
    }

    public function test_logs_after_id_returns_only_newer_entries(): void
    {
        $user        = User::factory()->create();
        $project     = $this->projectBelongingToUser($user);
        $environment = $this->environmentInProject($project);
        $run         = $this->terraformRunForEnvironment($environment);

        $log1 = RunLog::create(['terraform_run_id' => $run->id, 'environment_id' => $environment->id, 'source' => RunLog::SOURCE_TERRAFORM, 'level' => RunLog::LEVEL_INFO, 'message' => 'first']);
        RunLog::create(['terraform_run_id' => $run->id, 'environment_id' => $environment->id, 'source' => RunLog::SOURCE_TERRAFORM, 'level' => RunLog::LEVEL_INFO, 'message' => 'second']);
        RunLog::create(['terraform_run_id' => $run->id, 'environment_id' => $environment->id, 'source' => RunLog::SOURCE_TERRAFORM, 'level' => RunLog::LEVEL_INFO, 'message' => 'third']);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/projects/{$project->id}/environments/{$environment->id}/terraform-runs/{$run->id}/logs?after_id={$log1->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.message', 'second')
            ->assertJsonPath('data.1.message', 'third');
    }

    public function test_logs_after_id_zero_returns_all_entries(): void
    {
        $user        = User::factory()->create();
        $project     = $this->projectBelongingToUser($user);
        $environment = $this->environmentInProject($project);
        $run         = $this->terraformRunForEnvironment($environment);

        RunLog::create(['terraform_run_id' => $run->id, 'environment_id' => $environment->id, 'source' => RunLog::SOURCE_TERRAFORM, 'level' => RunLog::LEVEL_INFO, 'message' => 'only entry']);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/projects/{$project->id}/environments/{$environment->id}/terraform-runs/{$run->id}/logs?after_id=0");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_logs_returns_empty_when_no_logs_exist(): void
    {
        $user        = User::factory()->create();
        $project     = $this->projectBelongingToUser($user);
        $environment = $this->environmentInProject($project);
        $run         = $this->terraformRunForEnvironment($environment);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/projects/{$project->id}/environments/{$environment->id}/terraform-runs/{$run->id}/logs");

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_logs_returns_404_when_run_not_found(): void
    {
        $user        = User::factory()->create();
        $project     = $this->projectBelongingToUser($user);
        $environment = $this->environmentInProject($project);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/projects/{$project->id}/environments/{$environment->id}/terraform-runs/99999/logs");

        $response->assertStatus(404);
    }

    public function test_logs_returns_403_when_user_cannot_access_project(): void
    {
        $userA       = User::factory()->create();
        $userB       = User::factory()->create();
        $project     = $this->projectBelongingToUser($userB);
        $environment = $this->environmentInProject($project);
        $run         = $this->terraformRunForEnvironment($environment);

        $response = $this->withHeaders($this->authHeader($userA))
            ->getJson("/api/projects/{$project->id}/environments/{$environment->id}/terraform-runs/{$run->id}/logs");

        $response->assertStatus(403);
    }

    public function test_logs_does_not_return_logs_from_other_runs(): void
    {
        $user        = User::factory()->create();
        $project     = $this->projectBelongingToUser($user);
        $environment = $this->environmentInProject($project);
        $runA        = $this->terraformRunForEnvironment($environment);
        $runB        = $this->terraformRunForEnvironment($environment);

        RunLog::create(['terraform_run_id' => $runA->id, 'environment_id' => $environment->id, 'source' => RunLog::SOURCE_TERRAFORM, 'level' => RunLog::LEVEL_INFO, 'message' => 'log for run A']);
        RunLog::create(['terraform_run_id' => $runB->id, 'environment_id' => $environment->id, 'source' => RunLog::SOURCE_TERRAFORM, 'level' => RunLog::LEVEL_INFO, 'message' => 'log for run B']);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/projects/{$project->id}/environments/{$environment->id}/terraform-runs/{$runA->id}/logs");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.message', 'log for run A');
    }
}
