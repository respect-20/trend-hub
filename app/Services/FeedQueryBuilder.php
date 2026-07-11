<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use App\Models\UserPlatformPreference;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class FeedQueryBuilder
{
    public function enabledSources(User $user): array
    {
        $allSources = array_keys(config('trending.sources'));

        $disabledSources = UserPlatformPreference::where('user_id', $user->id)
            ->where('enabled', false)
            ->pluck('source')
            ->all();

        return array_values(array_diff($allSources, $disabledSources));
    }

    public function build(User $user, array $filters): Builder
    {
        $allSources = array_keys(config('trending.sources'));
        $enabledSources = $this->enabledSources($user);

        $sort = $filters['sort'] ?? 'trending';
        $search = $filters['search'] ?? null;
        $tag = $filters['tag'] ?? null;
        $range = $filters['range'] ?? 'all';
        $showHidden = $filters['show_hidden'] ?? false;

        $dismissedPostIds = $user->dismissals()->pluck('post_id');

        return Post::query()
            ->with('storyGroup.posts:id,source,title,story_group_id')
            ->when(count($enabledSources) < count($allSources), fn ($query) => $query->whereIn('source', $enabledSources))
            ->when($search, fn ($query) => $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('postTags', fn ($tagQuery) => $tagQuery->where('name', 'like', "%{$search}%"));
            }))
            ->when($tag, fn ($query) => $query->whereHas('postTags', fn ($q) => $q->where('name', $tag)))
            ->when($range !== 'all', fn ($query) => $query->where('published_at', '>=', $this->rangeStart($range)))
            ->when(! $showHidden, fn ($query) => $query->whereNotIn('id', $dismissedPostIds))
            ->when($sort === 'recent', fn ($query) => $query->orderByDesc('published_at'))
            ->when($sort !== 'recent', fn ($query) => $query->orderByDesc('trending_score'));
    }

    private function rangeStart(string $range): Carbon
    {
        return match ($range) {
            'today' => now()->startOfDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            default => now()->subCentury(),
        };
    }
}
