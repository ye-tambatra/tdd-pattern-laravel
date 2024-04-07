<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_authenticates_users_with_valid_credentials()
    {
        $email = 'user@example.com';
        $password = 'user';

        User::factory()->create([
            'email' => $email,
            'name' => 'user',
            'password' => Hash::make($password)
        ]);

        $response = $this
            ->postJson('/api/auth/login', [
                'email' => $email,
                'password' => $password
            ]);

        $response
            ->assertOk()
            ->assertJsonStructure(['token']);
    }

    public function test_it_unauthorizes_users_with_invalid_credentials()
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'name' => 'user'
        ]);

        // unprovided required credentials 
        $response = $this
            ->postJson('/api/auth/login');
        $response->assertUnprocessable();

        // email not found
        $response = $this
            ->postJson('/api/auth/login', [
                'email' => 'dumbuser@example.com',
                'password' => 'dumbpassword'
            ]);
        $response->assertNotFound();

        // incorrect password
        $response = $this
            ->postJson('/api/auth/login', [
                'email' => 'user@example.com',
                'password' => 'dumbpassword'
            ]);
        $response
            ->assertUnauthorized();
    }

    public function test_it_allows_only_authenticated_users_to_logout()
    {
        // unauthenticated users
        $response = $this
            ->postJson('/api/auth/logout');
        $response
            ->assertUnauthorized();

        // authenticated users
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $response = $this
            ->postJson('/api/auth/logout');

        $response->assertOk();
    }
}
