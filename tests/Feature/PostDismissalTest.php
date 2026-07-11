<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostDismissalTest extends TestCase
{
    use RefreshDatabase;

    public function test_dismissed_post_is_excluded_from_feed_by_default(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['title' => 'Hide me']);

        $this->actingAs($user)->post(route('dismissals.store', $post))->assertRedirect();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertInertia(fn ($page) => $page
            ->where('posts.data', fn ($posts) => collect($posts)->doesntContain(fn ($p) => $p['id'] === $post->id))
        );
    }

    public function test_show_hidden_reveals_dismissed_posts(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['title' => 'Hide me']);

        $this->actingAs($user)->post(route('dismissals.store', $post));

        $response = $this->actingAs($user)->get(route('dashboard', ['show_hidden' => 1]));

        $response->assertInertia(fn ($page) => $page
            ->where('posts.data', fn ($posts) => collect($posts)->contains(fn ($p) => $p['id'] === $post->id))
            ->where('dismissedPostIds', fn ($ids) => collect($ids)->contains($post->id))
        );
    }

    public function test_user_can_unhide_a_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)->post(route('dismissals.store', $post));
        $this->actingAs($user)->delete(route('dismissals.destroy', $post))->assertRedirect();

        $this->assertDatabaseMissing('post_dismissals', ['user_id' => $user->id, 'post_id' => $post->id]);
    }
}
