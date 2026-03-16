<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_home_endpoint_returns_welcome_payload(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Welcome to the AkoCloud API',
                'version' => '1.0.0',
            ]);
    }
}
