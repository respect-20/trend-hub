<?php

namespace Tests\Feature;

use App\Jobs\FetchTrendingPosts;
use App\Models\Post;
use App\Models\SourceFetchStatus;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FetchTrendingPostsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_fetches_and_stores_devto_posts_with_score_and_tags(): void
    {
        Http::fake([
            'dev.to/api/articles*' => Http::response([
                [
                    'id' => 111,
                    'title' => 'Understanding Laravel Queues',
                    'url' => 'https://dev.to/example/laravel-queues',
                    'user' => ['name' => 'Jane Dev'],
                    'cover_image' => 'https://dev.to/image.png',
                    'tag_list' => ['laravel', 'php'],
                    'public_reactions_count' => 10,
                    'comments_count' => 2,
                    'published_at' => now()->toIso8601String(),
                ],
                [
                    'id' => 112,
                    'title' => 'Cold Starts Explained',
                    'url' => 'https://dev.to/example/cold-starts',
                    'user' => ['name' => 'Sam Dev'],
                    'cover_image' => null,
                    'tag_list' => ['serverless'],
                    'public_reactions_count' => 100,
                    'comments_count' => 20,
                    'published_at' => now()->toIso8601String(),
                ],
            ]),
        ]);

        (new FetchTrendingPosts('devto'))->handle(
            app(\App\Services\TrendingScorer::class),
            app(\App\Services\StoryGrouper::class)
        );

        $this->assertSame(2, Post::count());

        $lowEngagement = Post::where('external_id', '111')->first();
        $highEngagement = Post::where('external_id', '112')->first();

        $this->assertSame(0, $lowEngagement->trending_score);
        $this->assertSame(100, $highEngagement->trending_score);
        $this->assertTrue($lowEngagement->postTags->pluck('name')->contains('laravel'));
        $this->assertSame(3, Tag::count());

        $status = SourceFetchStatus::where('source', 'devto')->first();
        $this->assertSame('success', $status->status);
        $this->assertSame(2, $status->posts_fetched);
    }

    public function test_records_failed_status_when_source_returns_nothing(): void
    {
        Http::fake([
            'dev.to/api/articles*' => Http::response([], 200),
        ]);

        (new FetchTrendingPosts('devto'))->handle(
            app(\App\Services\TrendingScorer::class),
            app(\App\Services\StoryGrouper::class)
        );

        $this->assertSame(0, Post::count());

        $status = SourceFetchStatus::where('source', 'devto')->first();
        $this->assertSame('failed', $status->status);
    }

    public function test_records_not_configured_status_for_producthunt_without_token(): void
    {
        config(['trending.producthunt.token' => null]);

        (new FetchTrendingPosts('producthunt'))->handle(
            app(\App\Services\TrendingScorer::class),
            app(\App\Services\StoryGrouper::class)
        );

        $this->assertSame(0, Post::count());

        $status = SourceFetchStatus::where('source', 'producthunt')->first();
        $this->assertSame('not_configured', $status->status);
    }

    public function test_updates_previous_trending_score_on_refetch(): void
    {
        Http::fake([
            'dev.to/api/articles*' => Http::response([
                [
                    'id' => 111,
                    'title' => 'Understanding Laravel Queues',
                    'url' => 'https://dev.to/example/laravel-queues',
                    'user' => ['name' => 'Jane Dev'],
                    'cover_image' => null,
                    'tag_list' => [],
                    'public_reactions_count' => 5,
                    'comments_count' => 0,
                    'published_at' => now()->toIso8601String(),
                ],
            ]),
        ]);

        $handle = fn () => (new FetchTrendingPosts('devto'))->handle(
            app(\App\Services\TrendingScorer::class),
            app(\App\Services\StoryGrouper::class)
        );

        $handle();
        $firstScore = Post::where('external_id', '111')->first()->trending_score;

        $handle();
        $post = Post::where('external_id', '111')->first();

        $this->assertSame($firstScore, $post->previous_trending_score);
    }
}
