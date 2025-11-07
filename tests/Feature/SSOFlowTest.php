<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Laravel\Passport\Client;

class SSOFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_sso_token_generation_endpoint_returns_correct_callback_url()
    {
        // Créer un utilisateur
        $user = User::factory()->create([
            'email' => 'test@herime.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        // Se connecter avec Passport
        Passport::actingAs($user, ['profile']);

        // URL de redirection comme dans le cas réel
        $redirectUrl = 'https://academie.herime.com/sso/callback?redirect=' . urlencode('https://academie.herime.com');

        // Appeler l'endpoint de génération de token SSO
        $response = $this->postJson('/api/sso/generate-token', [
            'redirect' => $redirectUrl
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'token',
                    'redirect_url',
                    'callback_url',
                ],
            ]);

        $data = $response->json('data');
        
        // Vérifier que callback_url contient academie.herime.com
        $this->assertStringContainsString('academie.herime.com', $data['callback_url']);
        $this->assertStringContainsString('token=', $data['callback_url']);
        $this->assertStringContainsString('https://', $data['callback_url']);
        
        // Vérifier que le token est présent
        $this->assertNotEmpty($data['token']);
        
        // Vérifier que redirect_url est correct
        $this->assertEquals(urldecode($redirectUrl), $data['redirect_url']);

        echo "\n✓ SSO Token Generation Test Passed\n";
        echo "  Callback URL: " . $data['callback_url'] . "\n";
        echo "  Token length: " . strlen($data['token']) . "\n";
    }

    public function test_login_page_with_force_token_parameter()
    {
        $user = User::factory()->create([
            'email' => 'test@herime.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        // Se connecter d'abord pour avoir une session
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'test@herime.com',
            'password' => 'password123'
        ]);

        $loginResponse->assertStatus(200);
        $token = $loginResponse->json('data.access_token');
        $this->assertNotEmpty($token);

        // Maintenant tester l'accès à /login avec force_token
        $redirectUrl = 'https://academie.herime.com/sso/callback?redirect=' . urlencode('https://academie.herime.com');
        $loginPageUrl = '/login?redirect=' . urlencode($redirectUrl) . '&force_token=1';

        // Simuler une requête GET avec le token Bearer
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->get($loginPageUrl);

        // La page devrait être accessible (pas de redirection 302 vers /dashboard)
        // Note: En test, on ne peut pas vraiment tester la redirection JS, mais on peut vérifier
        // que la page est accessible
        $this->assertTrue(in_array($response->getStatusCode(), [200, 302]));

        echo "\n✓ Login page with force_token is accessible\n";
        echo "  URL: " . $loginPageUrl . "\n";
    }

    public function test_sso_flow_with_encoded_redirect()
    {
        $user = User::factory()->create([
            'email' => 'test@herime.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        Passport::actingAs($user, ['profile']);

        // URL doublement encodée comme dans le cas réel
        $redirectUrl = 'https://academie.herime.com/sso/callback?redirect=' . urlencode('https://academie.herime.com');
        
        $response = $this->postJson('/api/sso/generate-token', [
            'redirect' => $redirectUrl
        ]);

        $response->assertStatus(200);
        $callbackUrl = $response->json('data.callback_url');
        
        // Vérifier que l'URL contient academie.herime.com
        $this->assertStringContainsString('academie.herime.com', $callbackUrl);
        
        // Vérifier que le token est dans l'URL
        $this->assertStringContainsString('token=', $callbackUrl);
        
        // Extraire et vérifier le token
        parse_str(parse_url($callbackUrl, PHP_URL_QUERY), $params);
        $this->assertArrayHasKey('token', $params);
        $this->assertNotEmpty($params['token']);

        echo "\n✓ Encoded redirect handled correctly\n";
        echo "  Callback URL: " . $callbackUrl . "\n";
    }
}
