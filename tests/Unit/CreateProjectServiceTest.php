<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\User;
use App\Repositories\ProjectRepository;
use App\Services\CreateProjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateProjectServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_project_service_creates_project(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['user_id' => $user->id]);
        $service = new CreateProjectService(new ProjectRepository());

        $project = $service->execute($organization->id, [
            'name' => 'Test Project',
            'description' => 'Test Description',
        ]);

        $this->assertNotNull($project->id);
        $this->assertEquals('Test Project', $project->name);
        $this->assertEquals($organization->id, $project->organization_id);
    }
}
