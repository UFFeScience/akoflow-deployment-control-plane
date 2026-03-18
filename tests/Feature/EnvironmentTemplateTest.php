<?php

namespace Tests\Feature;

use App\Models\EnvironmentTemplate;
use App\Models\EnvironmentTemplateVersion;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnvironmentTemplateTest extends TestCase
{
    use RefreshDatabase;

    private function authHeader(User $user): array
    {
        $token = $user->createToken('api-token')->plainTextToken;

        return ['Authorization' => "Bearer $token"];
    }

    public function test_user_can_list_environment_templates(): void
    {
        $user = User::factory()->create();
        $template = EnvironmentTemplate::create([
            'name' => 'Template One',
            'slug' => 'template-one',
            'description' => 'First template',
            'is_public' => true,
        ]);
        EnvironmentTemplateVersion::create([
            'template_id' => $template->id,
            'version' => 'v1',
            'definition_json' => ['steps' => []],
            'is_active' => true,
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson('/api/environment-templates');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_user_can_create_environment_template(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $slug = 'template-' . uniqid();

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson('/api/environment-templates', [
                'name' => 'New Template',
                'slug' => $slug,
                'description' => 'Runtime description',
                'is_public' => false,
                'owner_organization_id' => $organization->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.slug', $slug);

        $this->assertDatabaseHas('environment_templates', [
            'slug' => $slug,
            'owner_organization_id' => $organization->id,
        ]);
    }

    public function test_user_can_add_environment_template_version(): void
    {
        $user = User::factory()->create();
        $template = EnvironmentTemplate::create([
            'name' => 'Template Versioned',
            'slug' => 'template-versioned',
            'description' => 'Versioned template',
            'is_public' => false,
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson("/api/environment-templates/{$template->id}/versions", [
                'version' => 'v2',
                'definition_json' => ['graph' => ['nodes' => []]],
                'is_active' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.template_id', (string) $template->id)
            ->assertJsonPath('data.version', 'v2');

        $this->assertDatabaseHas('environment_template_versions', [
            'template_id' => $template->id,
            'version' => 'v2',
        ]);
    }

    public function test_user_can_get_environment_template_by_id(): void
    {
        $user = User::factory()->create();
        $template = EnvironmentTemplate::create([
            'name'         => 'Findable Template',
            'slug'         => 'findable-template',
            'description'  => 'A template to fetch by id',
            'is_public'    => true,
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/environment-templates/{$template->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $template->id)
            ->assertJsonPath('data.slug', 'findable-template');
    }

    public function test_show_template_returns_404_for_nonexistent_id(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson('/api/environment-templates/99999');

        $response->assertStatus(404);
    }

    public function test_user_can_list_environment_template_versions(): void
    {
        $user = User::factory()->create();
        $template = EnvironmentTemplate::create([
            'name'         => 'Versioned Template',
            'slug'         => 'versioned-template-list',
            'is_public'    => true,
        ]);
        EnvironmentTemplateVersion::create([
            'template_id'     => $template->id,
            'version'         => 'v1',
            'definition_json' => ['steps' => []],
            'is_active'       => false,
        ]);
        EnvironmentTemplateVersion::create([
            'template_id'     => $template->id,
            'version'         => 'v2',
            'definition_json' => ['steps' => []],
            'is_active'       => true,
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/environment-templates/{$template->id}/versions");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_list_versions_returns_404_for_nonexistent_template(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson('/api/environment-templates/99999/versions');

        $response->assertStatus(404);
    }

    public function test_user_can_get_environment_template_version_by_id(): void
    {
        $user = User::factory()->create();
        $template = EnvironmentTemplate::create([
            'name'         => 'Template Show Version',
            'slug'         => 'template-show-version',
            'is_public'    => true,
        ]);
        $version = EnvironmentTemplateVersion::create([
            'template_id'     => $template->id,
            'version'         => 'v3',
            'definition_json' => ['steps' => []],
            'is_active'       => true,
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/environment-templates/{$template->id}/versions/{$version->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $version->id)
            ->assertJsonPath('data.version', 'v3');
    }

    public function test_show_version_returns_404_for_nonexistent_version(): void
    {
        $user = User::factory()->create();
        $template = EnvironmentTemplate::create([
            'name'         => 'Template 404 Version',
            'slug'         => 'template-404-version',
            'is_public'    => true,
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/environment-templates/{$template->id}/versions/99999");

        $response->assertStatus(404);
    }

    public function test_user_can_activate_environment_template_version(): void
    {
        $user = User::factory()->create();
        $template = EnvironmentTemplate::create([
            'name'         => 'Template Activate',
            'slug'         => 'template-activate',
            'is_public'    => true,
        ]);
        $v1 = EnvironmentTemplateVersion::create([
            'template_id'     => $template->id,
            'version'         => 'v1',
            'definition_json' => ['steps' => []],
            'is_active'       => true,
        ]);
        $v2 = EnvironmentTemplateVersion::create([
            'template_id'     => $template->id,
            'version'         => 'v2',
            'definition_json' => ['steps' => []],
            'is_active'       => false,
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->patchJson("/api/environment-templates/{$template->id}/versions/{$v2->id}/activate");

        $response->assertStatus(200)
            ->assertJsonPath('data.version', 'v2');

        $this->assertDatabaseHas('environment_template_versions', [
            'id'        => $v2->id,
            'is_active' => true,
        ]);
    }

    public function test_activate_version_returns_404_for_nonexistent_version(): void
    {
        $user = User::factory()->create();
        $template = EnvironmentTemplate::create([
            'name'         => 'Template Activate 404',
            'slug'         => 'template-activate-404',
            'is_public'    => true,
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->patchJson("/api/environment-templates/{$template->id}/versions/99999/activate");

        $response->assertStatus(404);
    }

    public function test_user_can_get_active_version_for_template(): void
    {
        $user = User::factory()->create();
        $template = EnvironmentTemplate::create([
            'name'         => 'Template Active Version',
            'slug'         => 'template-active-version',
            'is_public'    => true,
        ]);
        EnvironmentTemplateVersion::create([
            'template_id'     => $template->id,
            'version'         => 'v1',
            'definition_json' => ['steps' => []],
            'is_active'       => false,
        ]);
        $active = EnvironmentTemplateVersion::create([
            'template_id'     => $template->id,
            'version'         => 'v2',
            'definition_json' => ['steps' => []],
            'is_active'       => true,
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/environment-templates/{$template->id}/versions/active");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $active->id)
            ->assertJsonPath('data.version', 'v2');
    }
}
