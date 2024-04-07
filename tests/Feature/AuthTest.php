<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

        $response = $this->postJson('/api/auth/login', [
            'email' => $email,
            'password' => $password
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure(['token']);
    }

    public function test_it_unauthorizes_users_with_invalid_credentials()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistinguser@example.com',
            'password' => 'dumbpassword'
        ]);

        $response
            ->assertUnauthorized();
    }

    public function test_only_authenticated_users_can_logout()
    {
        $response = $this->postJson('/api/auth/logout');
        $response
            ->assertUnauthorized();

        $user = User::factory()->create();
        $token = $user->createToken('TOKEN_TEST')->plainTextToken;

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/auth/logout');

        $response->assertOk();
    }
}
