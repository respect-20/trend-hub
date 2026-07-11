<?php

namespace App\Services;

use App\Models\StoryGroup;
use Illuminate\Support\Str;

class StoryGrouper
{
    private const SIMILARITY_THRESHOLD = 85.0;

    /**
     * Group posts covering the same story across platforms. Tries an exact
     * normalized-title match first, then falls back to a fuzzy similarity
     * check against groups created in the last week (catches near-identical
     * headlines like "X launches Y" vs "X launches Y — here's what it means").
     */
    public function resolveGroupId(string $title, string $url): int
    {
        $normalized = $this->normalize($title);

        $group = StoryGroup::where('canonical_title', $normalized)->first()
            ?? $this->findSimilarGroup($normalized);

        if ($group) {
            return $group->id;
        }

        return StoryGroup::create([
            'canonical_title' => $normalized,
            'canonical_url' => $url,
        ])->id;
    }

    private function findSimilarGroup(string $normalized): ?StoryGroup
    {
        return StoryGroup::where('created_at', '>=', now()->subWeek())
            ->get(['id', 'canonical_title'])
            ->first(function (StoryGroup $candidate) use ($normalized) {
                similar_text($normalized, $candidate->canonical_title, $percent);

                return $percent >= self::SIMILARITY_THRESHOLD;
            });
    }

    private function normalize(string $title): string
    {
        return Str::of($title)
            ->lower()
            ->replaceMatches('/[^a-z0-9\s]/', '')
            ->squish()
            ->value();
    }
}
