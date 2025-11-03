<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Laravel\Passport\Passport;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SSOTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '+1234567890',
            'company' => 'Test Company',
            'position' => 'Developer',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'phone',
                        'company',
                        'position',
                    ],
                    'access_token',
                    'token_type',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);
    }

    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user',
                    'access_token',
                    'token_type',
                ],
            ]);
    }

    public function test_user_can_logout()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logout successful',
            ]);
    }

    public function test_authenticated_user_can_get_profile()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                    ],
                ],
            ]);
    }

    public function test_sso_token_validation()
    {
        $user = User::factory()->create();
        $token = $user->createToken('Test Token')->accessToken;

        $response = $this->postJson('/api/sso/validate-token', [
            'token' => $token,
            'client_domain' => 'test.herime.com',
        ]);

        $response->assertStatus(200)
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

    public function test_user_can_update_profile()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $updateData = [
            'name' => 'Updated Name',
            'phone' => '+9876543210',
            'company' => 'Updated Company',
        ];

        $response = $this->postJson('/api/user/profile', $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Profile updated successfully',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'phone' => '+9876543210',
            'company' => 'Updated Company',
        ]);
    }

    public function test_user_can_change_password()
    {
        $user = User::factory()->create([
            'password' => bcrypt('oldpassword'),
        ]);
        Passport::actingAs($user);

        $passwordData = [
            'current_password' => 'oldpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ];

        $response = $this->putJson('/api/user/password', $passwordData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Password changed successfully',
            ]);
    }

    public function test_user_can_get_sessions()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->getJson('/api/sso/sessions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'sessions',
                ],
            ]);
    }

    public function test_user_can_revoke_session()
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        // Créer une session de test
        $session = $user->sessions()->create([
            'session_id' => 'test-session-123',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Browser',
            'device_name' => 'Test Device',
            'platform' => 'Test Platform',
            'browser' => 'Test Browser',
            'is_current' => false,
        ]);

        $response = $this->deleteJson("/api/sso/sessions/{$session->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Session revoked successfully',
            ]);
    }

    public function test_invalid_credentials_rejected()
    {
        $loginData = [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials',
            ]);
    }

    public function test_unauthenticated_access_rejected()
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401);
    }
}
