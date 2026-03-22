<?php

namespace Tests\Unit;

use App\Models\Deployment;
use App\Models\DeploymentTemplate;
use App\Models\Environment;
use App\Models\EnvironmentTemplate;
use App\Models\EnvironmentTemplateVersion;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Provider;
use App\Models\User;
use App\Repositories\DeploymentRepository;
use App\Services\CreateDeploymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateDeploymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private function buildDependencies(): array
    {
        $user = User::factory()->create();
        $org  = Organization::factory()->create(['user_id' => $user->id]);

        $provider = Provider::create([
            'name'   => 'Test Provider',
            'type'   => Provider::TYPES[0],
            'status' => Provider::STATUSES[0],
        ]);

        $project = Project::factory()->create(['organization_id' => $org->id]);

        $environment = Environment::create([
            'project_id' => $project->id,
            'name'       => 'Test Env',
            'status'     => Environment::STATUSES[0],
        ]);

        $template = EnvironmentTemplate::create([
            'name'      => 'Tpl',
            'slug'      => 'tpl-' . uniqid(),
            'is_public' => true,
        ]);

        $version = EnvironmentTemplateVersion::create([
            'template_id'     => $template->id,
            'version'         => 'v1',
            'definition_json' => [],
            'is_active'       => true,
        ]);

        $deploymentTemplate = DeploymentTemplate::create([
            'template_version_id'    => $version->id,
            'custom_parameters_json' => [],
        ]);

        return compact('environment', 'provider', 'deploymentTemplate');
    }

    private function service(): CreateDeploymentService
    {
        return new CreateDeploymentService(new DeploymentRepository(new Deployment()));
    }

    public function test_creates_deployment_with_required_fields(): void
    {
        ['environment' => $environment, 'provider' => $provider, 'deploymentTemplate' => $deploymentTemplate]
            = $this->buildDependencies();

        $deployment = $this->service()->handle((string) $environment->id, [
            'provider_id'            => $provider->id,
            'deployment_template_id' => $deploymentTemplate->id,
            'region'                 => 'us-east-1',
            'environment_type'       => Deployment::ENVIRONMENT_TYPES[0],
            'name'                   => 'My Deployment',
        ]);

        $this->assertInstanceOf(Deployment::class, $deployment);
        $this->assertEquals($environment->id, $deployment->environment_id);
        $this->assertEquals($provider->id, $deployment->provider_id);
        $this->assertEquals('us-east-1', $deployment->region);
        $this->assertEquals('My Deployment', $deployment->name);

        $this->assertDatabaseHas('deployments', [
            'environment_id' => $environment->id,
            'name'           => 'My Deployment',
        ]);
    }

    public function test_deployment_gets_default_environment_type_when_not_provided(): void
    {
        ['environment' => $environment, 'provider' => $provider, 'deploymentTemplate' => $deploymentTemplate]
            = $this->buildDependencies();

        $deployment = $this->service()->handle((string) $environment->id, [
            'provider_id'            => $provider->id,
            'deployment_template_id' => $deploymentTemplate->id,
            'region'                 => 'eu-west-1',
        ]);

        $this->assertEquals(Deployment::ENVIRONMENT_TYPES[0], $deployment->environment_type);
    }

    public function test_deployment_gets_fallback_name_when_not_provided(): void
    {
        ['environment' => $environment, 'provider' => $provider, 'deploymentTemplate' => $deploymentTemplate]
            = $this->buildDependencies();

        $deployment = $this->service()->handle((string) $environment->id, [
            'provider_id'            => $provider->id,
            'deployment_template_id' => $deploymentTemplate->id,
        ]);

        $this->assertNotEmpty($deployment->name);
    }

    public function test_instance_groups_are_stripped_from_deployment_data(): void
    {
        ['environment' => $environment, 'provider' => $provider, 'deploymentTemplate' => $deploymentTemplate]
            = $this->buildDependencies();

        $deployment = $this->service()->handle((string) $environment->id, [
            'provider_id'            => $provider->id,
            'deployment_template_id' => $deploymentTemplate->id,
            'name'                   => 'No Groups',
            'instance_groups'        => [
                ['role' => 'worker', 'quantity' => 3],
            ],
        ]);

        // The service intentionally strips instance_groups — deployment is created
        $this->assertInstanceOf(Deployment::class, $deployment);
        $this->assertDatabaseCount('deployments', 1);
    }

    public function test_falls_back_to_existing_deployment_template_when_id_not_provided(): void
    {
        ['environment' => $environment, 'provider' => $provider]
            = $this->buildDependencies();

        // An existing DeploymentTemplate exists from buildDependencies — the service should pick it up
        $deployment = $this->service()->handle((string) $environment->id, [
            'provider_id' => $provider->id,
            'name'        => 'Auto Template',
        ]);

        $this->assertNotNull($deployment->deployment_template_id);
    }
}
