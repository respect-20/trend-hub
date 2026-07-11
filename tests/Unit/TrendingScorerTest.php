<?php

namespace Tests\Unit;

use App\Fetchers\NormalizedPost;
use App\Services\TrendingScorer;
use PHPUnit\Framework\TestCase;

class TrendingScorerTest extends TestCase
{
    private function post(string $source, string $externalId, array $engagement): NormalizedPost
    {
        return new NormalizedPost(
            source: $source,
            externalId: $externalId,
            title: 'Title '.$externalId,
            url: 'https://example.com/'.$externalId,
            author: null,
            thumbnailUrl: null,
            tags: [],
            rawEngagement: $engagement,
            publishedAt: null,
        );
    }

    public function test_normalizes_scores_between_zero_and_hundred(): void
    {
        $scorer = new TrendingScorer();

        $posts = [
            $this->post('devto', '1', ['reactions' => 0, 'comments' => 0]),
            $this->post('devto', '2', ['reactions' => 50, 'comments' => 0]),
            $this->post('devto', '3', ['reactions' => 100, 'comments' => 0]),
        ];

        $scores = $scorer->scoreBatch($posts);

        $this->assertSame(0, $scores['1']);
        $this->assertSame(50, $scores['2']);
        $this->assertSame(100, $scores['3']);
    }

    public function test_returns_fifty_for_all_when_engagement_is_identical(): void
    {
        $scorer = new TrendingScorer();

        $posts = [
            $this->post('hackernews', '1', ['points' => 10, 'comments' => 2]),
            $this->post('hackernews', '2', ['points' => 10, 'comments' => 2]),
        ];

        $scores = $scorer->scoreBatch($posts);

        $this->assertSame(50, $scores['1']);
        $this->assertSame(50, $scores['2']);
    }

    public function test_returns_empty_array_for_empty_batch(): void
    {
        $scorer = new TrendingScorer();

        $this->assertSame([], $scorer->scoreBatch([]));
    }

    public function test_unknown_source_scores_zero_metric(): void
    {
        $scorer = new TrendingScorer();

        $posts = [
            $this->post('unknown-source', '1', ['whatever' => 999]),
            $this->post('unknown-source', '2', ['whatever' => 999]),
        ];

        // identical (zero) metrics across the batch fall back to the 50 baseline
        $scores = $scorer->scoreBatch($posts);

        $this->assertSame(50, $scores['1']);
        $this->assertSame(50, $scores['2']);
    }
}
