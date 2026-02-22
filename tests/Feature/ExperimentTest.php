<?php

namespace Tests\Feature;

use App\Models\Experiment;
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

    public function test_user_can_list_experiments_by_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        Experiment::create([
            'project_id' => $project->id,
            'name' => 'Experiment A',
            'status' => Experiment::STATUSES[0],
        ]);
        Experiment::create([
            'project_id' => $project->id,
            'name' => 'Experiment B',
            'status' => Experiment::STATUSES[1],
        ]);
        Experiment::create([
            'project_id' => $project->id,
            'name' => 'Experiment C',
            'status' => Experiment::STATUSES[2],
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/projects/{$project->id}/experiments");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_create_experiment(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson("/api/projects/{$project->id}/experiments", [
                'name' => 'New Experiment',
                'status' => Experiment::STATUSES[1],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New Experiment')
            ->assertJsonPath('data.project_id', (string) $project->id);

        $this->assertDatabaseHas('experiments', [
            'project_id' => $project->id,
            'name' => 'New Experiment',
        ]);
    }
}
