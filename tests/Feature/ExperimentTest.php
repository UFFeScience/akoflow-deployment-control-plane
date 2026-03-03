<?php

namespace Tests\Feature;

use App\Models\Experiment;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExperimentTest extends TestCase
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

    public function test_user_can_list_experiments_by_project(): void
    {
        $user    = User::factory()->create();
        $project = $this->projectBelongingToUser($user);

        Experiment::create(['project_id' => $project->id, 'name' => 'Experiment A', 'status' => Experiment::STATUSES[0]]);
        Experiment::create(['project_id' => $project->id, 'name' => 'Experiment B', 'status' => Experiment::STATUSES[1]]);
        Experiment::create(['project_id' => $project->id, 'name' => 'Experiment C', 'status' => Experiment::STATUSES[2]]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/projects/{$project->id}/experiments");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_create_experiment(): void
    {
        $user    = User::factory()->create();
        $project = $this->projectBelongingToUser($user);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson("/api/projects/{$project->id}/experiments", [
                'name'   => 'New Experiment',
                'status' => Experiment::STATUSES[1],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New Experiment')
            ->assertJsonPath('data.project_id', (string) $project->id);

        $this->assertDatabaseHas('experiments', [
            'project_id' => $project->id,
            'name'       => 'New Experiment',
        ]);
    }

    public function test_user_cannot_list_experiments_of_another_users_project(): void
    {
        $userA   = User::factory()->create();
        $userB   = User::factory()->create();
        $project = $this->projectBelongingToUser($userB); // owned by userB

        $response = $this->withHeaders($this->authHeader($userA))
            ->getJson("/api/projects/{$project->id}/experiments");

        $response->assertStatus(403);
    }
}
