<?php

namespace Tests\Unit;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\ChangePasswordService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChangePasswordServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): ChangePasswordService
    {
        return new ChangePasswordService(new UserRepository(new User()));
    }

    public function test_returns_true_and_updates_password_when_current_password_is_correct(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('current-password'),
        ]);

        $result = $this->service()->execute($user, 'current-password', 'new-secure-password');

        $this->assertTrue($result);

        $user->refresh();
        $this->assertTrue(Hash::check('new-secure-password', $user->password));
    }

    public function test_returns_false_when_current_password_is_wrong(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password'),
        ]);

        $result = $this->service()->execute($user, 'wrong-password', 'new-password');

        $this->assertFalse($result);

        $user->refresh();
        $this->assertTrue(Hash::check('correct-password', $user->password), 'Password must not be changed');
    }

    public function test_old_password_no_longer_works_after_change(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-pass'),
        ]);

        $this->service()->execute($user, 'old-pass', 'brand-new-pass');

        $user->refresh();
        $this->assertFalse(Hash::check('old-pass', $user->password));
    }
}
