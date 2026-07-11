<?php

namespace App\Services;

use App\Fetchers\NormalizedPost;

class TrendingScorer
{
    /**
     * @param  NormalizedPost[]  $posts
     * @return array<string, int> external_id => 0-100 score
     */
    public function scoreBatch(array $posts): array
    {
        $metrics = [];

        foreach ($posts as $post) {
            $metrics[$post->externalId] = $this->primaryMetric($post);
        }

        if (empty($metrics)) {
            return [];
        }

        $min = min($metrics);
        $max = max($metrics);

        if ($max === $min) {
            return array_map(fn () => 50, $metrics);
        }

        return array_map(
            fn ($value) => (int) round((($value - $min) / ($max - $min)) * 100),
            $metrics
        );
    }

    private function primaryMetric(NormalizedPost $post): int
    {
        $engagement = $post->rawEngagement;

        return match ($post->source) {
            'devto' => ($engagement['reactions'] ?? 0) + ($engagement['comments'] ?? 0) * 2,
            'hackernews' => ($engagement['points'] ?? 0) + ($engagement['comments'] ?? 0),
            'stackoverflow' => ($engagement['score'] ?? 0) * 3
                + ($engagement['answers'] ?? 0) * 5
                + (int) (($engagement['views'] ?? 0) / 100),
            'producthunt' => ($engagement['votes'] ?? 0) + ($engagement['comments'] ?? 0) * 2,
            'lobsters' => ($engagement['score'] ?? 0) * 2 + ($engagement['comments'] ?? 0),
            'mastodon' => ($engagement['favourites'] ?? 0) + ($engagement['reblogs'] ?? 0) * 2 + ($engagement['replies'] ?? 0),
            default => 0,
        };
    }
}
