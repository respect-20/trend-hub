<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RssFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_rss_feed_returns_xml_with_posts(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['title' => 'A trending story']);

        $response = $this->get(route('feed.rss', $user->rssToken()));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');
        $response->assertSee('A trending story', false);
        $response->assertSee($post->url, false);
    }

    public function test_invalid_token_returns_404(): void
    {
        $this->get(route('feed.rss', 'not-a-real-token'))->assertNotFound();
    }
}
