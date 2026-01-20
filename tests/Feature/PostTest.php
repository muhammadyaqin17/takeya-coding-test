<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
    }

    /*
    |--------------------------------------------------------------------------
    | Index Tests
    |--------------------------------------------------------------------------
    */

    public function test_posts_index_returns_paginated_published_posts_with_user_data(): void
    {
        // Create published posts
        Post::factory()->count(25)->create([
            'user_id' => $this->user->id,
            'is_draft' => false,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->getJson('/posts');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'content', 'user_id', 'is_draft', 'published_at', 'user'],
                ],
                'current_page',
                'per_page',
                'total',
            ])
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('per_page', 20);
    }

    public function test_posts_index_excludes_draft_posts(): void
    {
        Post::factory()->create([
            'user_id' => $this->user->id,
            'is_draft' => true,
            'title' => 'Draft Post',
        ]);

        Post::factory()->create([
            'user_id' => $this->user->id,
            'is_draft' => false,
            'published_at' => now()->subDay(),
            'title' => 'Published Post',
        ]);

        $response = $this->getJson('/posts');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Published Post');
    }

    public function test_posts_index_excludes_scheduled_posts(): void
    {
        Post::factory()->create([
            'user_id' => $this->user->id,
            'is_draft' => false,
            'published_at' => now()->addDay(),
            'title' => 'Scheduled Post',
        ]);

        Post::factory()->create([
            'user_id' => $this->user->id,
            'is_draft' => false,
            'published_at' => now()->subDay(),
            'title' => 'Published Post',
        ]);

        $response = $this->getJson('/posts');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Published Post');
    }

    /*
    |--------------------------------------------------------------------------
    | Show Tests
    |--------------------------------------------------------------------------
    */

    public function test_posts_show_returns_a_published_post_as_json(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'is_draft' => false,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->getJson("/posts/{$post->id}");

        $response->assertOk()
            ->assertJsonStructure(['id', 'title', 'content', 'user_id', 'is_draft', 'published_at']);
    }

    public function test_posts_show_returns_404_for_draft_post(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'is_draft' => true,
        ]);

        $response = $this->getJson("/posts/{$post->id}");

        $response->assertNotFound();
    }

    public function test_posts_show_returns_404_for_scheduled_post(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'is_draft' => false,
            'published_at' => now()->addDay(),
        ]);

        $response = $this->getJson("/posts/{$post->id}");

        $response->assertNotFound();
    }

    /*
    |--------------------------------------------------------------------------
    | Create Tests
    |--------------------------------------------------------------------------
    */

    public function test_guests_cannot_access_posts_create(): void
    {
        $response = $this->get('/posts/create');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_users_can_access_posts_create(): void
    {
        $response = $this->actingAs($this->user)->get('/posts/create');

        $response->assertOk()
            ->assertSee('posts.create');
    }

    /*
    |--------------------------------------------------------------------------
    | Store Tests
    |--------------------------------------------------------------------------
    */

    public function test_guests_cannot_create_posts(): void
    {
        $response = $this->postJson('/posts', [
            'title' => 'New Post',
            'content' => 'Post content',
        ]);

        $response->assertUnauthorized();
    }

    public function test_authenticated_users_can_create_posts(): void
    {
        $response = $this->actingAs($this->user)->postJson('/posts', [
            'title' => 'New Post',
            'content' => 'Post content',
            'is_draft' => false,
            'published_at' => now()->toDateTimeString(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('title', 'New Post');

        $this->assertDatabaseHas('posts', [
            'title' => 'New Post',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_posts_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)->postJson('/posts', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'content']);
    }

    /*
    |--------------------------------------------------------------------------
    | Edit Tests
    |--------------------------------------------------------------------------
    */

    public function test_only_the_author_can_access_posts_edit(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->get("/posts/{$post->id}/edit");

        $response->assertOk()
            ->assertSee('posts.edit');
    }

    public function test_non_author_cannot_access_posts_edit(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->otherUser)->get("/posts/{$post->id}/edit");

        $response->assertForbidden();
    }

    public function test_guests_cannot_access_posts_edit(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->get("/posts/{$post->id}/edit");

        $response->assertRedirect('/login');
    }

    /*
    |--------------------------------------------------------------------------
    | Update Tests
    |--------------------------------------------------------------------------
    */

    public function test_only_the_author_can_update_posts(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->putJson("/posts/{$post->id}", [
            'title' => 'Updated Title',
            'content' => 'Updated content',
        ]);

        $response->assertOk()
            ->assertJsonPath('title', 'Updated Title');

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_non_author_cannot_update_posts(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->otherUser)->putJson("/posts/{$post->id}", [
            'title' => 'Updated Title',
            'content' => 'Updated content',
        ]);

        $response->assertForbidden();
    }

    public function test_posts_update_validates_required_fields(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->putJson("/posts/{$post->id}", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'content']);
    }

    /*
    |--------------------------------------------------------------------------
    | Destroy Tests
    |--------------------------------------------------------------------------
    */

    public function test_only_the_author_can_delete_posts(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->deleteJson("/posts/{$post->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_non_author_cannot_delete_posts(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->otherUser)->deleteJson("/posts/{$post->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }
}
