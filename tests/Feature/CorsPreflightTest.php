<?php

namespace Tests\Feature;

use Tests\TestCase;

class CorsPreflightTest extends TestCase
{
    public function test_preflight_options_returns_cors_headers(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'https://academie.herime.com',
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'Content-Type, Authorization',
        ])->options('/api/sso/generate-token');

        $response->assertStatus(204);
        $response->assertHeader('Access-Control-Allow-Origin', 'https://academie.herime.com');
        $response->assertHeader('Access-Control-Allow-Methods', 'POST');
        $response->assertHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        $response->assertHeader('Access-Control-Allow-Credentials', 'true');
    }

    public function test_unauthorized_request_includes_cors_headers(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'https://academie.herime.com',
            'Accept' => 'application/json',
        ])->postJson('/api/sso/generate-token', [
            'redirect' => 'https://academie.herime.com/sso/callback',
        ]);

        $response->assertStatus(401);
        $response->assertHeader('Access-Control-Allow-Origin', 'https://academie.herime.com');
        $response->assertHeader('Access-Control-Allow-Credentials', 'true');
    }
}
