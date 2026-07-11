<?php

namespace Tests\Feature;

use App\Fetchers\MastodonFetcher;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MastodonFetcherTest extends TestCase
{
    public function test_maps_mastodon_trending_statuses_into_normalized_posts(): void
    {
        Http::fake([
            'mastodon.social/api/v1/trends/statuses*' => Http::response([
                [
                    'id' => '123456',
                    'created_at' => '2026-07-10T10:00:00.000Z',
                    'content' => '<p>Check out this <a href="#">cool project</a></p>',
                    'url' => 'https://mastodon.social/@user/123456',
                    'account' => ['display_name' => 'Jane Doe', 'username' => 'jane'],
                    'reblogs_count' => 10,
                    'favourites_count' => 20,
                    'replies_count' => 3,
                    'tags' => [['name' => 'opensource']],
                    'media_attachments' => [],
                ],
            ]),
        ]);

        $posts = (new MastodonFetcher())->fetch();

        $this->assertCount(1, $posts);
        $this->assertSame('mastodon', $posts[0]->source);
        $this->assertSame('123456', $posts[0]->externalId);
        $this->assertSame('Check out this cool project', $posts[0]->title);
        $this->assertSame('Jane Doe', $posts[0]->author);
        $this->assertSame(['opensource'], $posts[0]->tags);
        $this->assertSame(20, $posts[0]->rawEngagement['favourites']);
    }

    public function test_skips_media_only_posts_with_no_text_content(): void
    {
        Http::fake([
            'mastodon.social/api/v1/trends/statuses*' => Http::response([
                [
                    'id' => '1',
                    'created_at' => '2026-07-10T10:00:00.000Z',
                    'content' => '',
                    'url' => 'https://mastodon.social/@user/1',
                    'account' => ['display_name' => 'Jane', 'username' => 'jane'],
                    'reblogs_count' => 0,
                    'favourites_count' => 0,
                    'replies_count' => 0,
                    'tags' => [],
                    'media_attachments' => [],
                ],
                [
                    'id' => '2',
                    'created_at' => '2026-07-10T10:00:00.000Z',
                    'content' => '<p>Real post</p>',
                    'url' => 'https://mastodon.social/@user/2',
                    'account' => ['display_name' => 'Jane', 'username' => 'jane'],
                    'reblogs_count' => 0,
                    'favourites_count' => 0,
                    'replies_count' => 0,
                    'tags' => [],
                    'media_attachments' => [],
                ],
            ]),
        ]);

        $posts = (new MastodonFetcher())->fetch();

        $this->assertCount(1, $posts);
        $this->assertSame('2', $posts[0]->externalId);
    }
}
