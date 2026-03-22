<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\User;
use App\Models\Provider;
use App\Repositories\OrganizationRepository;
use App\Repositories\OrganizationUserRepository;
use App\Repositories\ProviderRepository;
use App\Services\CreateOrganizationService;
use App\Services\SeedOrganizationDefaultProvidersService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateOrganizationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_organization_service_creates_organization(): void
    {
        $user = User::factory()->create();
        $service = new CreateOrganizationService(
            new OrganizationRepository(),
            new OrganizationUserRepository(),
            new SeedOrganizationDefaultProvidersService(new ProviderRepository(new Provider()))
        );

        $organization = $service->execute($user, [
            'name' => 'Test Organization',
            'description' => 'Test Description',
        ]);

        $this->assertNotNull($organization->id);
        $this->assertEquals('Test Organization', $organization->name);
        $this->assertEquals($user->id, $organization->user_id);
    }

    public function test_create_organization_service_adds_creator_as_owner(): void
    {
        $user = User::factory()->create();
        $service = new CreateOrganizationService(
            new OrganizationRepository(),
            new OrganizationUserRepository(),
            new SeedOrganizationDefaultProvidersService(new ProviderRepository(new Provider()))
        );

        $organization = $service->execute($user, [
            'name' => 'Test Organization',
            'description' => 'Test Description',
        ]);

        $this->assertDatabaseHas('organization_users', [
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'role' => 'owner',
        ]);
    }
}
