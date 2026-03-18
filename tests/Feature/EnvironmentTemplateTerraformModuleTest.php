<?php

namespace Tests\Feature;

use App\Models\EnvironmentTemplate;
use App\Models\EnvironmentTemplateTerraformModule;
use App\Models\EnvironmentTemplateVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnvironmentTemplateTerraformModuleTest extends TestCase
{
    use RefreshDatabase;

    private function authHeader(User $user): array
    {
        $token = $user->createToken('api-token')->plainTextToken;

        return ['Authorization' => "Bearer $token"];
    }

    private function templateWithVersion(): array
    {
        $template = EnvironmentTemplate::create([
            'name'      => 'TF Module Template',
            'slug'      => 'tf-module-template-' . uniqid(),
            'is_public' => true,
        ]);

        $version = EnvironmentTemplateVersion::create([
            'template_id'     => $template->id,
            'version'         => 'v1',
            'definition_json' => ['steps' => []],
            'is_active'       => true,
        ]);

        return [$template, $version];
    }

    private function base(EnvironmentTemplate $t, EnvironmentTemplateVersion $v): string
    {
        return "/api/environment-templates/{$t->id}/versions/{$v->id}/terraform-modules";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // index
    // ─────────────────────────────────────────────────────────────────────────

    public function test_index_returns_empty_array_when_no_modules_exist(): void
    {
        $user = User::factory()->create();
        [$template, $version] = $this->templateWithVersion();

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson($this->base($template, $version));

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_index_returns_all_modules_for_version(): void
    {
        $user = User::factory()->create();
        [$template, $version] = $this->templateWithVersion();

        EnvironmentTemplateTerraformModule::create([
            'template_version_id' => $version->id,
            'provider_type'       => 'aws',
            'module_slug'         => 'aws_nvflare',
        ]);
        EnvironmentTemplateTerraformModule::create([
            'template_version_id' => $version->id,
            'provider_type'       => 'gcp',
            'module_slug'         => 'gcp_gke',
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson($this->base($template, $version));

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // show
    // ─────────────────────────────────────────────────────────────────────────

    public function test_show_returns_404_when_no_module_for_provider(): void
    {
        $user = User::factory()->create();
        [$template, $version] = $this->templateWithVersion();

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("{$this->base($template, $version)}/aws");

        $response->assertStatus(404);
    }

    public function test_user_can_get_terraform_module_by_provider(): void
    {
        $user = User::factory()->create();
        [$template, $version] = $this->templateWithVersion();

        EnvironmentTemplateTerraformModule::create([
            'template_version_id' => $version->id,
            'provider_type'       => 'aws',
            'module_slug'         => 'aws_nvflare',
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("{$this->base($template, $version)}/aws");

        $response->assertStatus(200)
            ->assertJsonPath('module_slug', 'aws_nvflare')
            ->assertJsonPath('provider_type', 'aws');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // upsert
    // ─────────────────────────────────────────────────────────────────────────

    public function test_user_can_create_aws_module(): void
    {
        $user = User::factory()->create();
        [$template, $version] = $this->templateWithVersion();

        $response = $this->withHeaders($this->authHeader($user))
            ->putJson("{$this->base($template, $version)}/aws", [
                'module_slug' => 'aws_nvflare',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('module_slug', 'aws_nvflare')
            ->assertJsonPath('provider_type', 'aws');

        $this->assertDatabaseHas('environment_template_terraform_modules', [
            'template_version_id' => $version->id,
            'provider_type'       => 'aws',
            'module_slug'         => 'aws_nvflare',
        ]);
    }

    public function test_user_can_create_gcp_module_independently(): void
    {
        $user = User::factory()->create();
        [$template, $version] = $this->templateWithVersion();

        $response = $this->withHeaders($this->authHeader($user))
            ->putJson("{$this->base($template, $version)}/gcp", [
                'module_slug' => 'gcp_gke',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('module_slug', 'gcp_gke')
            ->assertJsonPath('provider_type', 'gcp');
    }

    public function test_same_version_can_have_aws_and_gcp_modules(): void
    {
        $user = User::factory()->create();
        [$template, $version] = $this->templateWithVersion();

        $this->withHeaders($this->authHeader($user))
            ->putJson("{$this->base($template, $version)}/aws", ['module_slug' => 'aws_nvflare'])
            ->assertStatus(201);

        $this->withHeaders($this->authHeader($user))
            ->putJson("{$this->base($template, $version)}/gcp", ['module_slug' => 'gcp_gke'])
            ->assertStatus(201);

        $this->assertDatabaseCount('environment_template_terraform_modules', 2);
    }

    public function test_user_can_create_module_with_custom_hcl(): void
    {
        $user = User::factory()->create();
        [$template, $version] = $this->templateWithVersion();

        $response = $this->withHeaders($this->authHeader($user))
            ->putJson("{$this->base($template, $version)}/aws", [
                'main_tf'      => 'resource "aws_instance" "example" {}',
                'variables_tf' => 'variable "region" {}',
                'outputs_tf'   => 'output "id" { value = aws_instance.example.id }',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('provider_type', 'aws');

        $this->assertDatabaseHas('environment_template_terraform_modules', [
            'template_version_id' => $version->id,
            'provider_type'       => 'aws',
        ]);
    }

    public function test_user_can_update_existing_module(): void
    {
        $user = User::factory()->create();
        [$template, $version] = $this->templateWithVersion();

        EnvironmentTemplateTerraformModule::create([
            'template_version_id' => $version->id,
            'provider_type'       => 'aws',
            'module_slug'         => 'aws_nvflare',
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->putJson("{$this->base($template, $version)}/aws", [
                'module_slug' => 'aws_nvflare',
                'main_tf'     => 'resource "aws_instance" "updated" {}',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('provider_type', 'aws');

        $this->assertDatabaseHas('environment_template_terraform_modules', [
            'template_version_id' => $version->id,
            'provider_type'       => 'aws',
            'module_slug'         => 'aws_nvflare',
        ]);
    }

    public function test_upsert_rejects_invalid_builtin_slug(): void
    {
        $user = User::factory()->create();
        [$template, $version] = $this->templateWithVersion();

        $response = $this->withHeaders($this->authHeader($user))
            ->putJson("{$this->base($template, $version)}/aws", [
                'module_slug' => 'invalid_slug_xyz',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['module_slug']);
    }

    public function test_user_can_set_tfvars_mapping_and_credential_env_keys(): void
    {
        $user = User::factory()->create();
        [$template, $version] = $this->templateWithVersion();

        $tfvarsMapping = [
            'environment_configuration' => ['project_id' => 'gcp_project'],
        ];
        $credentialEnvKeys = ['AWS_ACCESS_KEY_ID', 'AWS_SECRET_ACCESS_KEY'];

        $response = $this->withHeaders($this->authHeader($user))
            ->putJson("{$this->base($template, $version)}/aws", [
                'module_slug'          => 'aws_nvflare',
                'tfvars_mapping_json'  => $tfvarsMapping,
                'credential_env_keys'  => $credentialEnvKeys,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('module_slug', 'aws_nvflare');

        $module = EnvironmentTemplateTerraformModule::where('template_version_id', $version->id)
            ->where('provider_type', 'aws')
            ->firstOrFail();

        $this->assertEquals($credentialEnvKeys, $module->credential_env_keys);
    }
    // ─────────────────────────────────────────────────────────────────────────
    // show
    // ─────────────────────────────────────────────────────────────────────────

    public function test_show_returns_404_when_no_terraform_module_for_version(): void
    {
        $user = User::factory()->create();
        [$template, $version] = $this->templateWithVersion();

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/environment-templates/{$template->id}/versions/{$version->id}/terraform-module");

        $response->assertStatus(404)
            ->assertJsonPath('message', 'No Terraform module found for this version.');
    }

    public function test_user_can_get_terraform_module_for_version(): void
    {
        $user = User::factory()->create();
        [$template, $version] = $this->templateWithVersion();

        EnvironmentTemplateTerraformModule::create([
            'template_version_id' => $version->id,
            'module_slug'         => 'aws_nvflare',
            'provider_type'       => 'aws',
        ]);

        $response = $this->withHeaders($this->authHeader($user))
            ->getJson("/api/environment-templates/{$template->id}/versions/{$version->id}/terraform-module");

        $response->assertStatus(200)
            ->assertJsonPath('module_slug', 'aws_nvflare')
            ->assertJsonPath('provider_type', 'aws');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // upsert
    // ─────────────────────────────────────────────────────────────────────────

    public function test_user_can_create_terraform_module_with_builtin_slug(): void
    {
        $user = User::factory()->create();
        [$template, $version] = $this->templateWithVersion();

        $response = $this->withHeaders($this->authHeader($user))
            ->putJson("/api/environment-templates/{$template->id}/versions/{$version->id}/terraform-module", [
                'module_slug' => 'aws_nvflare',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('module_slug', 'aws_nvflare')
            ->assertJsonPath('provider_type', 'aws');

        $this->assertDatabaseHas('environment_template_terraform_modules', [
            'template_version_id' => $version->id,
            'module_slug'         => 'aws_nvflare',
        ]);
    }

    public function test_user_can_create_terraform_module_with_gcp_builtin_slug(): void
    {
        $user = User::factory()->create();
        [$template, $version] = $this->templateWithVersion();

        $response = $this->withHeaders($this->authHeader($user))
            ->putJson("/api/environment-templates/{$template->id}/versions/{$version->id}/terraform-module", [
                'module_slug' => 'gcp_gke',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('module_slug', 'gcp_gke')
            ->assertJsonPath('provider_type', 'gcp');
    }

    public function test_user_can_create_terraform_module_with_custom_hcl(): void
    {
        $user = User::factory()->create();
        [$template, $version] = $this->templateWithVersion();

        $response = $this->withHeaders($this->authHeader($user))
            ->putJson("/api/environment-templates/{$template->id}/versions/{$version->id}/terraform-module", [
                'provider_type' => 'aws',
                'main_tf'       => 'resource "aws_instance" "example" {}',
                'variables_tf'  => 'variable "region" {}',
                'outputs_tf'    => 'output "id" { value = aws_instance.example.id }',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('provider_type', 'aws');

        $this->assertDatabaseHas('environment_template_terraform_modules', [
            'template_version_id' => $version->id,
            'provider_type'       => 'aws',
        ]);
    }

    public function test_user_can_update_existing_terraform_module(): void
    {
        $user = User::factory()->create();
        [$template, $version] = $this->templateWithVersion();

        // Create initial module
        EnvironmentTemplateTerraformModule::create([
            'template_version_id' => $version->id,
            'module_slug'         => 'aws_nvflare',
            'provider_type'       => 'aws',
        ]);

        // Update via upsert
        $response = $this->withHeaders($this->authHeader($user))
            ->putJson("/api/environment-templates/{$template->id}/versions/{$version->id}/terraform-module", [
                'module_slug' => 'gcp_gke',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('module_slug', 'gcp_gke')
            ->assertJsonPath('provider_type', 'gcp');

        $this->assertDatabaseHas('environment_template_terraform_modules', [
            'template_version_id' => $version->id,
            'module_slug'         => 'gcp_gke',
        ]);
    }

}
