<?php

namespace App\Fetchers;

use App\Fetchers\Contracts\FetcherInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DevToFetcher implements FetcherInterface
{
    public function source(): string
    {
        return 'devto';
    }

    public function fetch(): array
    {
        $response = Http::get('https://dev.to/api/articles', [
            'top' => 7,
            'per_page' => 30,
        ]);

        if ($response->failed()) {
            Log::warning('DevToFetcher request failed', ['status' => $response->status()]);

            return [];
        }

        return collect($response->json())->map(function (array $article) {
            $reactions = $article['public_reactions_count'] ?? 0;
            $comments = $article['comments_count'] ?? 0;

            return new NormalizedPost(
                source: $this->source(),
                externalId: (string) $article['id'],
                title: $article['title'],
                url: $article['url'],
                author: $article['user']['name'] ?? null,
                thumbnailUrl: $article['cover_image'] ?? $article['social_image'] ?? null,
                tags: $article['tag_list'] ?? [],
                rawEngagement: [
                    'reactions' => $reactions,
                    'comments' => $comments,
                ],
                publishedAt: isset($article['published_at']) ? \Carbon\Carbon::parse($article['published_at']) : null,
            );
        })->all();
    }
}
