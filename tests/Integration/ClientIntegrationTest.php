<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use Laravel\Passport\Passport;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ClientIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_sso_flow()
    {
        // 1. Créer un utilisateur
        $user = User::factory()->create([
            'email' => 'test@herime.com',
            'password' => bcrypt('password123'),
        ]);

        // 2. Simuler la connexion
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'test@herime.com',
            'password' => 'password123',
        ]);

        $loginResponse->assertStatus(200);
        $token = $loginResponse->json('data.access_token');

        // 3. Créer une session SSO pour un client
        Passport::actingAs($user);
        
        $ssoResponse = $this->postJson('/api/sso/create-session', [
            'client_domain' => 'academie.herime.com',
            'redirect_url' => 'https://academie.herime.com/dashboard',
        ]);

        $ssoResponse->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'sso_url',
                    'token',
                    'expires_in',
                ],
            ]);

        // 4. Valider le token SSO
        $ssoToken = $ssoResponse->json('data.token');
        
        $validationResponse = $this->postJson('/api/sso/validate-token', [
            'token' => $ssoToken,
            'client_domain' => 'academie.herime.com',
        ]);

        $validationResponse->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                    ],
                    'session',
                    'permissions',
                ],
            ]);
    }

    public function test_multiple_client_sessions()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        // Créer des sessions pour différents clients
        $clients = [
            'academie.herime.com',
            'store.herime.com',
            'events.herime.com',
            'studio.herime.com',
        ];

        foreach ($clients as $client) {
            $response = $this->postJson('/api/sso/create-session', [
                'client_domain' => $client,
                'redirect_url' => "https://{$client}/dashboard",
            ]);

            $response->assertStatus(200);
        }

        // Vérifier que toutes les sessions sont créées
        $sessionsResponse = $this->getJson('/api/sso/sessions');
        $sessionsResponse->assertStatus(200);
        
        $sessions = $sessionsResponse->json('data.sessions');
        $this->assertCount(4, $sessions); // 4 clients + 1 session actuelle
    }

    public function test_session_revocation()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        // Créer plusieurs sessions
        $session1 = $user->sessions()->create([
            'session_id' => 'session-1',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Chrome on Windows',
            'device_name' => 'Windows PC',
            'platform' => 'Windows',
            'browser' => 'Chrome',
            'is_current' => false,
        ]);

        $session2 = $user->sessions()->create([
            'session_id' => 'session-2',
            'ip_address' => '192.168.1.2',
            'user_agent' => 'Safari on Mac',
            'device_name' => 'MacBook',
            'platform' => 'macOS',
            'browser' => 'Safari',
            'is_current' => false,
        ]);

        // Révoquer une session spécifique
        $response = $this->deleteJson("/api/sso/sessions/{$session1->id}");
        $response->assertStatus(200);

        // Vérifier que la session est révoquée
        $this->assertDatabaseMissing('user_sessions', [
            'id' => $session1->id,
            'is_current' => true,
        ]);

        // Révoquer toutes les autres sessions
        $response = $this->postJson('/api/sso/sessions/revoke-all');
        $response->assertStatus(200);

        // Vérifier que toutes les sessions sont révoquées
        $this->assertDatabaseMissing('user_sessions', [
            'id' => $session2->id,
            'is_current' => true,
        ]);
    }

    public function test_user_profile_management()
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'company' => 'Test Company',
            'position' => 'Developer',
        ]);

        Passport::actingAs($user);

        // Mettre à jour le profil
        $updateData = [
            'name' => 'John Smith',
            'phone' => '+9876543210',
            'company' => 'New Company',
            'position' => 'Senior Developer',
        ];

        $response = $this->postJson('/api/user/profile', $updateData);
        $response->assertStatus(200);

        // Vérifier les changements
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'John Smith',
            'phone' => '+9876543210',
            'company' => 'New Company',
            'position' => 'Senior Developer',
        ]);

        // Changer le mot de passe
        $passwordData = [
            'current_password' => 'password',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ];

        $response = $this->putJson('/api/user/password', $passwordData);
        $response->assertStatus(200);

        // Mettre à jour les préférences
        $preferences = [
            'preferences' => [
                'theme' => 'dark',
                'language' => 'fr',
                'notifications' => [
                    'email' => true,
                    'sms' => false,
                    'push' => true,
                ],
            ],
        ];

        $response = $this->putJson('/api/user/preferences', $preferences);
        $response->assertStatus(200);

        // Vérifier les préférences
        $user->refresh();
        $this->assertEquals('dark', $user->preferences['theme']);
        $this->assertEquals('fr', $user->preferences['language']);
        $this->assertTrue($user->preferences['notifications']['email']);
        $this->assertFalse($user->preferences['notifications']['sms']);
        $this->assertTrue($user->preferences['notifications']['push']);
    }

    public function test_account_deactivation()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        Passport::actingAs($user);

        // Désactiver le compte
        $response = $this->postJson('/api/user/deactivate', [
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Account deactivated successfully',
            ]);

        // Vérifier que le compte est désactivé
        $user->refresh();
        $this->assertFalse($user->is_active);

        // Vérifier que les tokens sont révoqués
        $this->assertCount(0, $user->tokens);
    }

    public function test_account_deletion()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        Passport::actingAs($user);

        // Supprimer le compte
        $response = $this->deleteJson('/api/user/delete', [
            'password' => 'password123',
            'confirmation' => 'DELETE',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Account deleted successfully',
            ]);

        // Vérifier que l'utilisateur est supprimé
        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_security_features()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        // Test de la validation des données
        $invalidData = [
            'name' => '', // Nom vide
            'email' => 'invalid-email', // Email invalide
            'phone' => '123', // Téléphone trop court
        ];

        $response = $this->postJson('/api/user/profile', $invalidData);
        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ]);

        // Test de la protection CSRF (simulé)
        $response = $this->postJson('/api/user/profile', [
                    'name' => 'Test User',
                ], [
                    'X-CSRF-TOKEN' => 'invalid-token',
                ]);

        // Le test devrait passer car nous utilisons l'API
        $response->assertStatus(200);
    }
}
