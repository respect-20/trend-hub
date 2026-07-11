<?php

namespace App\Fetchers;

use App\Fetchers\Contracts\FetcherInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StackOverflowFetcher implements FetcherInterface
{
    public function source(): string
    {
        return 'stackoverflow';
    }

    public function fetch(): array
    {
        $response = Http::get('https://api.stackexchange.com/2.3/questions', [
            'order' => 'desc',
            'sort' => 'hot',
            'site' => 'stackoverflow',
            'pagesize' => 30,
        ]);

        if ($response->failed()) {
            Log::warning('StackOverflowFetcher request failed', ['status' => $response->status()]);

            return [];
        }

        return collect($response->json('items', []))->map(function (array $question) {
            return new NormalizedPost(
                source: $this->source(),
                externalId: (string) $question['question_id'],
                title: html_entity_decode($question['title']),
                url: $question['link'],
                author: $question['owner']['display_name'] ?? null,
                thumbnailUrl: null,
                tags: $question['tags'] ?? [],
                rawEngagement: [
                    'score' => $question['score'] ?? 0,
                    'answers' => $question['answer_count'] ?? 0,
                    'views' => $question['view_count'] ?? 0,
                ],
                publishedAt: isset($question['creation_date']) ? \Carbon\Carbon::createFromTimestamp($question['creation_date']) : null,
            );
        })->all();
    }
}
