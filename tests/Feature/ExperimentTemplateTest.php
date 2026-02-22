<?php

namespace Tests\Feature;

use App\Models\ExperimentTemplate;
use App\Models\ExperimentTemplateVersion;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExperimentTemplateTest extends TestCase
{
    use RefreshDatabase;

    private function authHeader(User $user): array
    {
        $token = $user->createToken('api-token')->plainTextToken;

        return ['Authorization' => "Bearer $token"];
    }

    public function test_user_can_list_experiment_templates(): void
    {
        $user = User::factory()->create();
        $template = ExperimentTemplate::create([
            'name' => 'Template One',
            'slug' => 'template-one',
            'runtime_type' => ExperimentTemplate::RUNTIME_TYPES[0],
            'description' => 'First template',
            'is_public' => true,
        ]);
        ExperimentTemplateVersion::create([
            'template_id' => $template->id,
            'version' => 'v1',
            'definition_json' => ['steps' => []],
            'is_active' => true,
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson('/api/experiment-templates');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_user_can_create_experiment_template(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $slug = 'template-' . uniqid();

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson('/api/experiment-templates', [
                'name' => 'New Template',
                'slug' => $slug,
                'runtime_type' => ExperimentTemplate::RUNTIME_TYPES[1],
                'description' => 'Runtime description',
                'is_public' => false,
                'owner_organization_id' => $organization->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.slug', $slug)
            ->assertJsonPath('data.runtime_type', ExperimentTemplate::RUNTIME_TYPES[1]);

        $this->assertDatabaseHas('experiment_templates', [
            'slug' => $slug,
            'owner_organization_id' => $organization->id,
        ]);
    }

    public function test_user_can_add_experiment_template_version(): void
    {
        $user = User::factory()->create();
        $template = ExperimentTemplate::create([
            'name' => 'Template Versioned',
            'slug' => 'template-versioned',
            'runtime_type' => ExperimentTemplate::RUNTIME_TYPES[2],
            'description' => 'Versioned template',
            'is_public' => false,
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson("/api/experiment-templates/{$template->id}/versions", [
                'version' => 'v2',
                'definition_json' => ['graph' => ['nodes' => []]],
                'is_active' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.template_id', (string) $template->id)
            ->assertJsonPath('data.version', 'v2');

        $this->assertDatabaseHas('experiment_template_versions', [
            'template_id' => $template->id,
            'version' => 'v2',
        ]);
    }
}
