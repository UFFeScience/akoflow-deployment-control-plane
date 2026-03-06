<?php

namespace Tests\Feature;

use App\Jobs\DestroyExperimentJob;
use App\Jobs\ProvisionExperimentJob;
use App\Models\Experiment;
use App\Models\Organization;
use App\Models\Project;
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

    private function experimentInProject(Project $project): Experiment
    {
        return Experiment::create([
            'project_id' => $project->id,
            'name'       => 'Test Experiment',
            'status'     => Experiment::STATUSES[0],
        ]);
    }

    private function terraformRunForExperiment(Experiment $experiment, string $status = TerraformRun::STATUS_APPLIED): TerraformRun
    {
        return TerraformRun::create([
            'experiment_id' => $experiment->id,
            'status'        => $status,
            'action'        => TerraformRun::ACTION_APPLY,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // index
    // ─────────────────────────────────────────────────────────────────────────

    public function test_user_can_list_terraform_runs_for_experiment(): void
    {
        $user       = User::factory()->create();
        $project    = $this->projectBelongingToUser($user);
        $experiment = $this->experimentInProject($project);

        $this->terraformRunForExperiment($experiment, TerraformRun::STATUS_APPLIED);
        $this->terraformRunForExperiment($experiment, TerraformRun::STATUS_FAILED);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/projects/{$project->id}/experiments/{$experiment->id}/terraform-runs");

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    public function test_user_cannot_list_terraform_runs_of_another_users_project(): void
    {
        $userA      = User::factory()->create();
        $userB      = User::factory()->create();
        $project    = $this->projectBelongingToUser($userB);
        $experiment = $this->experimentInProject($project);

        $response = $this->withHeaders($this->authHeader($userA))
            ->getJson("/api/projects/{$project->id}/experiments/{$experiment->id}/terraform-runs");

        $response->assertStatus(403);
    }

    public function test_list_terraform_runs_returns_404_when_experiment_not_found(): void
    {
        $user    = User::factory()->create();
        $project = $this->projectBelongingToUser($user);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/projects/{$project->id}/experiments/99999/terraform-runs");

        $response->assertStatus(404);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // show
    // ─────────────────────────────────────────────────────────────────────────

    public function test_user_can_get_terraform_run_by_id(): void
    {
        $user       = User::factory()->create();
        $project    = $this->projectBelongingToUser($user);
        $experiment = $this->experimentInProject($project);
        $run        = $this->terraformRunForExperiment($experiment);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/projects/{$project->id}/experiments/{$experiment->id}/terraform-runs/{$run->id}");

        $response->assertStatus(200)
            ->assertJsonPath('id', $run->id)
            ->assertJsonPath('status', TerraformRun::STATUS_APPLIED);
    }

    public function test_show_terraform_run_returns_404_when_run_not_found(): void
    {
        $user       = User::factory()->create();
        $project    = $this->projectBelongingToUser($user);
        $experiment = $this->experimentInProject($project);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/projects/{$project->id}/experiments/{$experiment->id}/terraform-runs/99999");

        $response->assertStatus(404);
    }

    public function test_show_terraform_run_returns_404_when_run_belongs_to_different_experiment(): void
    {
        $user        = User::factory()->create();
        $project     = $this->projectBelongingToUser($user);
        $experimentA = $this->experimentInProject($project);
        $experimentB = $this->experimentInProject($project);
        $run         = $this->terraformRunForExperiment($experimentA);

        // Try to access experimentA's run via experimentB's URL
        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/projects/{$project->id}/experiments/{$experimentB->id}/terraform-runs/{$run->id}");

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
        $experiment = $this->experimentInProject($project);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson("/api/projects/{$project->id}/experiments/{$experiment->id}/terraform-runs");

        $response->assertStatus(202)
            ->assertJsonPath('message', 'Provisioning job queued.');

        Queue::assertPushed(ProvisionExperimentJob::class);
    }

    public function test_user_cannot_trigger_provisioning_on_another_users_experiment(): void
    {
        Queue::fake();

        $userA      = User::factory()->create();
        $userB      = User::factory()->create();
        $project    = $this->projectBelongingToUser($userB);
        $experiment = $this->experimentInProject($project);

        $response = $this->withHeaders($this->authHeader($userA))
            ->postJson("/api/projects/{$project->id}/experiments/{$experiment->id}/terraform-runs");

        $response->assertStatus(403);

        Queue::assertNotPushed(ProvisionExperimentJob::class);
    }

    public function test_provision_returns_404_when_experiment_not_found(): void
    {
        Queue::fake();

        $user    = User::factory()->create();
        $project = $this->projectBelongingToUser($user);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson("/api/projects/{$project->id}/experiments/99999/terraform-runs");

        $response->assertStatus(404);

        Queue::assertNotPushed(ProvisionExperimentJob::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // destroy
    // ─────────────────────────────────────────────────────────────────────────

    public function test_user_can_trigger_destroy(): void
    {
        Queue::fake();

        $user       = User::factory()->create();
        $project    = $this->projectBelongingToUser($user);
        $experiment = $this->experimentInProject($project);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson("/api/projects/{$project->id}/experiments/{$experiment->id}/terraform-runs/destroy");

        $response->assertStatus(202)
            ->assertJsonPath('message', 'Destroy job queued.');

        Queue::assertPushed(DestroyExperimentJob::class);
    }

    public function test_user_cannot_trigger_destroy_on_another_users_experiment(): void
    {
        Queue::fake();

        $userA      = User::factory()->create();
        $userB      = User::factory()->create();
        $project    = $this->projectBelongingToUser($userB);
        $experiment = $this->experimentInProject($project);

        $response = $this->withHeaders($this->authHeader($userA))
            ->postJson("/api/projects/{$project->id}/experiments/{$experiment->id}/terraform-runs/destroy");

        $response->assertStatus(403);

        Queue::assertNotPushed(DestroyExperimentJob::class);
    }

    public function test_destroy_returns_404_when_experiment_not_found(): void
    {
        Queue::fake();

        $user    = User::factory()->create();
        $project = $this->projectBelongingToUser($user);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson("/api/projects/{$project->id}/experiments/99999/terraform-runs/destroy");

        $response->assertStatus(404);

        Queue::assertNotPushed(DestroyExperimentJob::class);
    }
}
