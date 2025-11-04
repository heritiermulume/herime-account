<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

class SSORedirectCompleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_sso_token_generation_endpoint()
    {
        // Créer un utilisateur
        $user = User::factory()->create([
            'email' => 'test@herime.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        // Se connecter avec Passport
        Passport::actingAs($user, ['profile']);

        // URL de redirection
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

        // Vérifier que callback_url contient academie.herime.com
        $callbackUrl = $response->json('data.callback_url');
        $this->assertStringContainsString('academie.herime.com', $callbackUrl);
        $this->assertStringContainsString('token=', $callbackUrl);

        echo "\n✓ Endpoint /api/sso/generate-token fonctionne\n";
        echo "  Callback URL: " . $callbackUrl . "\n";
    }

    public function test_sso_token_generation_with_encoded_redirect()
    {
        // Créer un utilisateur
        $user = User::factory()->create([
            'email' => 'test@herime.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        // Se connecter avec Passport
        Passport::actingAs($user, ['profile']);

        // URL doublement encodée (comme dans le cas réel)
        $redirectUrl = 'https://academie.herime.com/sso/callback?redirect=' . urlencode('https://academie.herime.com');

        // Appeler l'endpoint
        $response = $this->postJson('/api/sso/generate-token', [
            'redirect' => $redirectUrl
        ]);

        $response->assertStatus(200);
        $callbackUrl = $response->json('data.callback_url');
        $this->assertStringContainsString('academie.herime.com', $callbackUrl);

        echo "\n✓ URL encodée correctement gérée\n";
    }

    public function test_sso_token_generation_without_redirect()
    {
        // Créer un utilisateur
        $user = User::factory()->create([
            'email' => 'test@herime.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        // Se connecter avec Passport
        Passport::actingAs($user, ['profile']);

        // Appeler sans redirect (devrait retourner une erreur)
        $response = $this->postJson('/api/sso/generate-token', []);

        $response->assertStatus(422);
        
        echo "\n✓ Validation fonctionne (sans redirect)\n";
    }

    public function test_sso_token_generation_requires_authentication()
    {
        // Appeler sans authentification
        $response = $this->postJson('/api/sso/generate-token', [
            'redirect' => 'https://academie.herime.com/sso/callback'
        ]);

        $response->assertStatus(401);
        
        echo "\n✓ Authentification requise fonctionne\n";
    }
}
