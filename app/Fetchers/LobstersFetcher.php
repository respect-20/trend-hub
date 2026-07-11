<?php

namespace App\Fetchers;

use App\Fetchers\Contracts\FetcherInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LobstersFetcher implements FetcherInterface
{
    public function source(): string
    {
        return 'lobsters';
    }

    public function fetch(): array
    {
        $response = Http::get('https://lobste.rs/hottest.json');

        if ($response->failed()) {
            Log::warning('LobstersFetcher request failed', ['status' => $response->status()]);

            return [];
        }

        return collect($response->json())->map(function (array $story) {
            return new NormalizedPost(
                source: $this->source(),
                externalId: $story['short_id'],
                title: $story['title'],
                url: $story['url'] ?: $story['short_id_url'],
                author: $story['submitter_user'] ?? null,
                thumbnailUrl: null,
                tags: $story['tags'] ?? [],
                rawEngagement: [
                    'score' => $story['score'] ?? 0,
                    'comments' => $story['comment_count'] ?? 0,
                ],
                publishedAt: isset($story['created_at']) ? \Carbon\Carbon::parse($story['created_at']) : null,
            );
        })->all();
    }
}
