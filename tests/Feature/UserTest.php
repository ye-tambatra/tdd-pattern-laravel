<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
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

    public function test_it_ensures_that_only_root_can_create_admins()
    {
        $credentials = [
            'email' => 'user@example.com',
            'name' => 'user',
            'password' => 'password',
        ];

        // unauthenticated users
        $response = $this->postJson('/api/auth/register/admins', $credentials);
        $response
            ->assertUnauthorized();

        // authenticated as admin 
        Sanctum::actingAs(User::factory()->create(['email' => 'admin1@example.com', 'role' => 'admin']));
        $response = $this->postJson('/api/auth/register/admins', $credentials);
        $response
            ->assertUnauthorized();

        // authenticated as users 
        Sanctum::actingAs(User::factory()->create(['email' => 'user1@example.com', 'role' => 'user']));
        $response = $this->postJson('/api/auth/register/admins', $credentials);
        $response
            ->assertUnauthorized();

        // authenticated as root user
        Sanctum::actingAs(User::factory()->create(['email' => 'root@example.com', 'role' => 'root']));
        $response = $this->postJson('/api/auth/register/admins', $credentials);
        $response
            ->assertCreated();
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
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // unauthenticated users cannot delete 
        $response = $this->deleteJson('/api/users/' . $user1->id);
        $response->assertUnauthorized();

        // unauthenticated users cannot delete other users except themselves 
        Sanctum::actingAs($user1);
        $response = $this->deleteJson('/api/users/' . $user2->id);
        $response->assertUnauthorized();
        $response = $this->deleteJson('/api/users/' . $user1->id);
        $response->assertOk();
        $this->assertDatabaseMissing('users', ['id' => $user1->id]);

        // cannot delete non existing users 
        Sanctum::actingAs($user2);
        $response = $this->deleteJson('/api/users/7892');
        $response->assertNotFound();

        // admin and root can delete users
        $admin = User::factory()->create(['role' => 'admin']);
        $root = User::factory()->create(['role' => 'root']);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $this->actThenDelete($admin, $user1);
        $this->actThenDelete($root, $user2);

        // admin cannot delete other admins 
        $admin1 = User::factory()->create(['role' => 'admin']);
        $admin2 = User::factory()->create(['role' => 'admin']);
        $this->actThenAssertCannotDelete($admin1, $admin2);

        // root users cannot be deleted
        $admin = User::factory()->create(['role' => 'admin']);
        $root = User::factory()->create(['role' => 'root']);
        $this->actThenAssertCannotDelete($admin, $root);
        $this->actThenAssertCannotDelete($root, $root);

        // root can delete admins
        $this->actThenDelete($root, $admin);
    }

    private function actThenAssertCannotDelete($as, $delete)
    {
        Sanctum::actingAs($as);
        $response = $this->deleteJson('/api/users/' . $delete->id);
        return $response->assertUnauthorized();
    }

    private function actThenDelete($as, $delete)
    {
        Sanctum::actingAs($as);
        $response = $this->deleteJson('/api/users/' . $delete->id);
        $response->assertOk();
        $this->assertDatabaseMissing('users', ['id' => $delete->id]);
    }
}
