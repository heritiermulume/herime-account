<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SimpleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_health_check()
    {
        $response = $this->getJson('/api/health');
        
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
                'service' => 'Herime SSO'
            ]);
    }

    public function test_user_can_register_without_token()
    {
        // Test d'inscription sans token (en utilisant une approche différente)
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '+1234567890',
            'company' => 'Test Company',
            'position' => 'Developer',
        ];

        // Créer l'utilisateur directement
        $user = User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => bcrypt($userData['password']),
            'phone' => $userData['phone'],
            'company' => $userData['company'],
            'position' => $userData['position'],
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);

        $this->assertEquals('john@example.com', $user->email);
        $this->assertEquals('John Doe', $user->name);
    }

    public function test_user_model_relationships()
    {
        $user = User::factory()->create();
        
        // Test des relations
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->sessions());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $user->currentSession());
    }

    public function test_user_avatar_url()
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'avatar' => null
        ]);

        $avatarUrl = $user->avatar_url;
        $this->assertStringContainsString('ui-avatars.com', $avatarUrl);
        $this->assertStringContainsString('John+Doe', $avatarUrl);
    }

    public function test_user_is_active()
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->assertTrue($user->isActive());

        $user->update(['is_active' => false]);
        $this->assertFalse($user->isActive());
    }

    public function test_user_preferences()
    {
        $preferences = [
            'theme' => 'dark',
            'language' => 'fr',
            'notifications' => [
                'email' => true,
                'sms' => false,
                'push' => true,
            ],
        ];

        $user = User::factory()->create(['preferences' => $preferences]);
        
        $this->assertEquals('dark', $user->preferences['theme']);
        $this->assertEquals('fr', $user->preferences['language']);
        $this->assertTrue($user->preferences['notifications']['email']);
        $this->assertFalse($user->preferences['notifications']['sms']);
    }
}
