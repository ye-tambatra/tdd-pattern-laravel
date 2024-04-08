<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_allows_everyone_to_get_all_posts()
    {
        $posts = Post::factory(3)->for(User::factory())->create();

        // all post with pagination
        $response = $this->getJson('/api/posts?per_page=2&page=2');
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['total' => 3])
            ->assertJsonFragment(['per_page' => 2]);

        // one post
        $response = $this->getJson('/api/posts/' . $posts->random()->id);
        $response->assertOk();

        // non existing post
        $response = $this->getJson('/api/posts/48978');
        $response->assertNotFound();
    }

    public function test_it_allows_users_to_create_posts()
    {
        $user = $this->actAs('user');
        $post = Post::factory()->make();
        $response = $this->postJson('/api/posts', [
            'title' => $post->title,
            'content' => $post->content
        ]);
        $response->assertCreated();
        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id
        ]);

        // invalid request body 
        $response = $this->postJson('/api/posts', []);
        $response->assertUnprocessable();

        // admin cannot create post
        $this->actAs('admin');
        $response = $this->postJson('/api/posts', [
            'title' => $post->title,
            'content' => $post->content
        ]);
        $response->assertUnauthorized();

        // root cannot create post
        $this->actAs('root');
        $response = $this->postJson('/api/posts', [
            'title' => $post->title,
            'content' => $post->content
        ]);
        $response->assertUnauthorized();
    }

    public function test_it_allows_the_correponding_user_to_delete_post()
    {
        $user = $this->actAs('user');
        $posts = Post::factory(2)->for($user)->create();
        $response = $this->deleteJson('/api/posts/' . $posts[1]->id);
        $response->assertOk();
        $this->assertDatabaseMissing('posts', ['id' => $posts[1]->id]);

        // deleting non existing post
        $response = $this->deleteJson('/api/posts/4567');
        $response->assertNotFound();

        // user cannot delete other users post
        $anotherUser = $this->actAs('user');
        $response = $this->deleteJson('/api/posts/' . $posts[0]->id);
        $response->assertUnauthorized();

        // admin can delete whatever post they want
        $this->actAs('admin');
        $response = $this->deleteJson('/api/posts/' . $posts[0]->id);
        $response->assertOk();
        $this->assertDatabaseMissing('posts', ['id' => $posts[0]->id]);
    }

    public function test_it_allows_the_correponding_user_to_update_post()
    {
        $user = $this->actAs('user');
        $post = Post::factory()->for($user)->create();

        $response = $this->putJson('/api/posts/' . $post->id, [
            'title' => 'updated title'
        ]);
        $response->assertOk();
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'updated title'
        ]);

        // non owned post 
        $anotherUser = $this->actAs('user');
        $response = $this->putJson('/api/posts/' . $post->id, [
            'title' => 're-updated title'
        ]);
        $response->assertUnauthorized();
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'updated title' // asserting it still has its old title
        ]);

        // non existing post
        $response = $this->putJson('/api/posts/486', [
            'title' => 'updated title'
        ]);
        $response->assertNotFound();

        // admin cannot update any posts 
        $this->actAs('admin');
        $response = $this->putJson('/api/posts/' . $post->id, [
            'title' => 'admin updating post'
        ]);
        $response->assertUnauthorized();
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'updated title' // asserting it still has its old title
        ]);
    }

    private function actAs($role = 'user')
    {
        $user = User::factory()->create(['role' => $role]);
        Sanctum::actingAs($user);
        return $user;
    }
}
