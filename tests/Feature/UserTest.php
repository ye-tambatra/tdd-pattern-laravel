<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_registers_users_with_valid_credentials()
    {
        $credentials = [
            'email' => 'user@example.com',
            'name' => 'user',
            'password' => 'password'
        ];

        $response = $this->postJson('/api/auth/register', $credentials);
        $response
            ->assertCreated();
        $this
            ->assertDatabaseHas('users', [
                'email' => 'user@example.com',
                'name' => 'user',
            ]);
    }

    public function test_it_does_not_register_users_with_invalid_credentials()
    {
        // existing user
        User::factory()->create([
            'email' => 'duplicate@email.com'
        ]);
        $credentials = [
            'email' => 'duplicate@email.com',
            'name' => 'user',
            'password' => 'password'
        ];
        $response = $this->postJson('/api/auth/register', $credentials);
        $response
            ->assertConflict();

        // unprovided correct credentials
        $credentials = [
            'name' => 'user',
        ];
        $response = $this->postJson('/api/auth/register', $credentials);
        $response
            ->assertUnprocessable();
    }

    public function test_it_allows_users_to_delete_account()
    {
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);

        // unauthenticated users
        $response = $this->deleteJson('/api/users/' . $user1->id);
        $response->assertUnauthorized();

        // authenticated but trying to delete other users 
        Sanctum::actingAs($user1);
        $response = $this->deleteJson('/api/users/' . $user2->id);
        $response->assertUnauthorized();

        // authenticated users deleting themselves 
        Sanctum::actingAs($user1);
        $response = $this->deleteJson('/api/users/' . $user1->id);
        $response
            ->assertOk();
        $this->assertDatabaseMissing('users', [
            'id' => $user1->id
        ]);
    }
}
