<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrendingFeedFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_matches_title(): void
    {
        $user = User::factory()->create();
        Post::factory()->create(['title' => 'Laravel queues explained']);
        Post::factory()->create(['title' => 'Something unrelated']);

        $response = $this->actingAs($user)->get(route('dashboard', ['search' => 'queues']));

        $response->assertInertia(fn ($page) => $page
            ->where('posts.data', fn ($posts) => collect($posts)->count() === 1)
        );
    }

    public function test_search_matches_tag_name(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['title' => 'A totally unrelated title']);
        $tag = Tag::create(['name' => 'kubernetes']);
        $post->postTags()->attach($tag);

        Post::factory()->create(['title' => 'No tag here']);

        $response = $this->actingAs($user)->get(route('dashboard', ['search' => 'kubernetes']));

        $response->assertInertia(fn ($page) => $page
            ->where('posts.data', fn ($posts) => collect($posts)->count() === 1)
        );
    }

    public function test_tag_filter_restricts_to_matching_posts(): void
    {
        $user = User::factory()->create();
        $tagged = Post::factory()->create();
        $tag = Tag::create(['name' => 'rust']);
        $tagged->postTags()->attach($tag);

        Post::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard', ['tag' => 'rust']));

        $response->assertInertia(fn ($page) => $page
            ->where('posts.data', fn ($posts) => collect($posts)->count() === 1 && $posts[0]['id'] === $tagged->id)
        );
    }

    public function test_range_filter_excludes_older_posts(): void
    {
        $user = User::factory()->create();
        Post::factory()->create(['published_at' => now()->subHours(2)]);
        Post::factory()->create(['published_at' => now()->subMonths(2)]);

        $response = $this->actingAs($user)->get(route('dashboard', ['range' => 'today']));

        $response->assertInertia(fn ($page) => $page
            ->where('posts.data', fn ($posts) => collect($posts)->count() === 1)
        );
    }
}
