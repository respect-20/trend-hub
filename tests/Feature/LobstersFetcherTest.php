<?php

namespace Tests\Feature;

use App\Fetchers\LobstersFetcher;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LobstersFetcherTest extends TestCase
{
    public function test_maps_lobsters_response_into_normalized_posts(): void
    {
        Http::fake([
            'lobste.rs/hottest.json' => Http::response([
                [
                    'short_id' => 'abc123',
                    'created_at' => '2026-07-10T10:00:00.000-05:00',
                    'title' => 'A great systems programming post',
                    'url' => 'https://example.com/post',
                    'score' => 42,
                    'comment_count' => 7,
                    'submitter_user' => 'someuser',
                    'tags' => ['programming', 'rust'],
                    'short_id_url' => 'https://lobste.rs/s/abc123',
                ],
            ]),
        ]);

        $posts = (new LobstersFetcher())->fetch();

        $this->assertCount(1, $posts);
        $this->assertSame('lobsters', $posts[0]->source);
        $this->assertSame('abc123', $posts[0]->externalId);
        $this->assertSame('A great systems programming post', $posts[0]->title);
        $this->assertSame('https://example.com/post', $posts[0]->url);
        $this->assertSame('someuser', $posts[0]->author);
        $this->assertSame(['programming', 'rust'], $posts[0]->tags);
        $this->assertSame(42, $posts[0]->rawEngagement['score']);
    }

    public function test_falls_back_to_short_id_url_when_no_external_url(): void
    {
        Http::fake([
            'lobste.rs/hottest.json' => Http::response([
                [
                    'short_id' => 'abc123',
                    'created_at' => '2026-07-10T10:00:00.000-05:00',
                    'title' => 'Ask Lobsters: something',
                    'url' => '',
                    'score' => 5,
                    'comment_count' => 3,
                    'submitter_user' => 'someuser',
                    'tags' => [],
                    'short_id_url' => 'https://lobste.rs/s/abc123',
                ],
            ]),
        ]);

        $posts = (new LobstersFetcher())->fetch();

        $this->assertSame('https://lobste.rs/s/abc123', $posts[0]->url);
    }
}
