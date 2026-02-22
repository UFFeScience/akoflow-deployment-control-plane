<?php

namespace Tests\Unit;

use App\Repositories\UserRepository;
use App\Services\RegisterUserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterUserServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_user_service_creates_user(): void
    {
        $service = new RegisterUserService(new UserRepository());

        $user = $service->execute([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $this->assertNotNull($user->id);
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
    }

    public function test_register_user_service_hashes_password(): void
    {
        $service = new RegisterUserService(new UserRepository());

        $user = $service->execute([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $this->assertNotEquals('password123', $user->password);
    }
}
