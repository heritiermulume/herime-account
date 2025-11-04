<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

class SSORedirectFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_sso_redirect_with_authenticated_user_and_force_token()
    {
        // Créer un utilisateur
        $user = User::factory()->create([
            'email' => 'test@herime.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        // Se connecter comme cet utilisateur
        $this->actingAs($user, 'web');

        // URL de redirection
        $redirectUrl = 'https://academie.herime.com/sso/callback?redirect=' . urlencode('https://academie.herime.com');
        $loginUrl = '/login?redirect=' . urlencode($redirectUrl) . '&force_token=1';

        // Faire la requête
        $response = $this->get($loginUrl);

        // Vérifier que c'est une redirection
        $response->assertStatus(302);

        // Vérifier que l'URL de redirection contient academie.herime.com
        $redirectLocation = $response->headers->get('Location');
        $this->assertStringContainsString('academie.herime.com', $redirectLocation);
        $this->assertStringContainsString('token=', $redirectLocation);
        
        echo "\n✓ Redirection SSO fonctionne avec utilisateur authentifié\n";
        echo "  URL de redirection: " . $redirectLocation . "\n";
    }

    public function test_sso_redirect_without_authentication()
    {
        // Ne pas se connecter
        $redirectUrl = 'https://academie.herime.com/sso/callback';
        $loginUrl = '/login?redirect=' . urlencode($redirectUrl) . '&force_token=1';

        // Faire la requête
        $response = $this->get($loginUrl);

        // Si pas connecté, devrait afficher la page de login (pas de redirection)
        $response->assertStatus(200);
        $response->assertViewIs('welcome');
        
        echo "\n✓ Affichage de la page de login si utilisateur non authentifié\n";
    }

    public function test_sso_redirect_without_force_token()
    {
        // Créer un utilisateur
        $user = User::factory()->create([
            'email' => 'test@herime.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        // Se connecter comme cet utilisateur
        $this->actingAs($user, 'web');

        // URL sans force_token
        $redirectUrl = 'https://academie.herime.com/sso/callback';
        $loginUrl = '/login?redirect=' . urlencode($redirectUrl);

        // Faire la requête
        $response = $this->get($loginUrl);

        // Sans force_token, devrait rediriger vers /dashboard
        $response->assertStatus(302);
        $response->assertRedirect('/dashboard');
        
        echo "\n✓ Redirection vers /dashboard si pas de force_token\n";
    }

    public function test_force_token_detection_various_formats()
    {
        // Créer un utilisateur
        $user = User::factory()->create([
            'email' => 'test@herime.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        // Se connecter comme cet utilisateur
        $this->actingAs($user, 'web');

        $redirectUrl = 'https://academie.herime.com/sso/callback';

        // Tester différents formats de force_token
        $formats = ['1', 'true', 'yes', 'on'];

        foreach ($formats as $format) {
            $loginUrl = '/login?redirect=' . urlencode($redirectUrl) . '&force_token=' . $format;
            $response = $this->get($loginUrl);
            
            $this->assertEquals(302, $response->getStatusCode(), "Failed for force_token={$format}");
            $location = $response->headers->get('Location');
            $this->assertStringContainsString('academie.herime.com', $location);
            echo "\n✓ Format force_token='{$format}' accepté\n";
        }
    }

    public function test_redirect_url_with_double_encoding()
    {
        // Créer un utilisateur
        $user = User::factory()->create([
            'email' => 'test@herime.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        // Se connecter comme cet utilisateur
        $this->actingAs($user, 'web');

        // URL doublement encodée (comme dans le cas réel)
        $innerUrl = urlencode('https://academie.herime.com');
        $redirectUrl = 'https://academie.herime.com/sso/callback?redirect=' . $innerUrl;
        $loginUrl = '/login?redirect=' . urlencode($redirectUrl) . '&force_token=1';

        $response = $this->get($loginUrl);

        $response->assertStatus(302);
        $location = $response->headers->get('Location');
        $this->assertStringContainsString('academie.herime.com', $location);
        $this->assertStringContainsString('token=', $location);
        
        echo "\n✓ URL doublement encodée correctement gérée\n";
        echo "  URL de redirection: " . $location . "\n";
    }
}
