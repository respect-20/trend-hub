<?php

namespace App\Fetchers;

use App\Fetchers\Contracts\FetcherInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MastodonFetcher implements FetcherInterface
{
    public function source(): string
    {
        return 'mastodon';
    }

    public function fetch(): array
    {
        $instance = config('trending.mastodon.instance', 'mastodon.social');

        $response = Http::get("https://{$instance}/api/v1/trends/statuses", ['limit' => 30]);

        if ($response->failed()) {
            Log::warning('MastodonFetcher request failed', ['status' => $response->status()]);

            return [];
        }

        return collect($response->json())
            ->map(function (array $status) {
                $title = trim(strip_tags($status['content'] ?? ''));

                if ($title === '') {
                    return null;
                }

                return new NormalizedPost(
                    source: $this->source(),
                    externalId: (string) $status['id'],
                    title: Str::limit($title, 200),
                    url: $status['url'],
                    author: $status['account']['display_name'] ?: $status['account']['username'] ?? null,
                    thumbnailUrl: $status['media_attachments'][0]['preview_url'] ?? null,
                    tags: collect($status['tags'] ?? [])->pluck('name')->all(),
                    rawEngagement: [
                        'reblogs' => $status['reblogs_count'] ?? 0,
                        'favourites' => $status['favourites_count'] ?? 0,
                        'replies' => $status['replies_count'] ?? 0,
                    ],
                    publishedAt: isset($status['created_at']) ? \Carbon\Carbon::parse($status['created_at']) : null,
                );
            })
            ->filter()
            ->values()
            ->all();
    }
}
