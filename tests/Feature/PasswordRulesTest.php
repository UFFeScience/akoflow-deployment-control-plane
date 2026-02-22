<?php

namespace Tests\Feature;

use Tests\TestCase;

class PasswordRulesTest extends TestCase
{
    public function test_password_rules_endpoint_returns_config(): void
    {
        $response = $this->getJson('/api/ui/password-rules');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'rules' => [
                    'min_length',
                    'require_numbers',
                    'require_special',
                    'require_mixed_case',
                    'ui',
                ],
            ]);
    }

    public function test_password_rules_render_endpoint_returns_ui_description(): void
    {
        $response = $this->getJson('/api/ui/render/password-rules');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'ui' => [
                    'fields',
                    'hints' => [
                        'min_length',
                        'require_numbers',
                        'require_special',
                        'require_mixed_case',
                    ],
                ],
            ]);
    }
}
