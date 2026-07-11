<?php

namespace App\Fetchers;

use App\Fetchers\Contracts\FetcherInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HackerNewsFetcher implements FetcherInterface
{
    public function source(): string
    {
        return 'hackernews';
    }

    public function fetch(): array
    {
        $topStories = Http::get('https://hacker-news.firebaseio.com/v0/topstories.json');

        if ($topStories->failed()) {
            Log::warning('HackerNewsFetcher top stories request failed', ['status' => $topStories->status()]);

            return [];
        }

        $ids = array_slice($topStories->json() ?? [], 0, 30);

        if (empty($ids)) {
            return [];
        }

        $responses = Http::pool(fn ($pool) => collect($ids)
            ->map(fn ($id) => $pool->as($id)->get("https://hacker-news.firebaseio.com/v0/item/{$id}.json"))
            ->all());

        $posts = [];

        foreach ($ids as $id) {
            $item = $responses[$id] ?? null;

            if (! $item || $item->failed() || empty($item->json())) {
                continue;
            }

            $data = $item->json();

            if (($data['type'] ?? null) !== 'story') {
                continue;
            }

            $posts[] = new NormalizedPost(
                source: $this->source(),
                externalId: (string) $data['id'],
                title: $data['title'] ?? 'Untitled',
                url: $data['url'] ?? "https://news.ycombinator.com/item?id={$data['id']}",
                author: $data['by'] ?? null,
                thumbnailUrl: null,
                tags: [],
                rawEngagement: [
                    'points' => $data['score'] ?? 0,
                    'comments' => $data['descendants'] ?? 0,
                ],
                publishedAt: isset($data['time']) ? \Carbon\Carbon::createFromTimestamp($data['time']) : null,
            );
        }

        return $posts;
    }
}
