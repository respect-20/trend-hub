<?php

namespace App\Http\Controllers;

use App\Models\SourceFetchStatus;
use App\Models\Tag;
use App\Services\FeedQueryBuilder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrendingController extends Controller
{
    public function index(Request $request, FeedQueryBuilder $feedQuery): Response
    {
        $user = $request->user();
        $allSources = array_keys(config('trending.sources'));

        $filters = [
            'sort' => $request->string('sort', 'trending')->value(),
            'search' => $request->string('search')->value(),
            'tag' => $request->string('tag')->value(),
            'range' => $request->string('range', 'all')->value(),
            'show_hidden' => $request->boolean('show_hidden'),
        ];

        $enabledSources = $feedQuery->enabledSources($user);
        $dismissedPostIds = $user->dismissals()->pluck('post_id');

        $posts = $feedQuery->build($user, $filters)
            ->paginate(20)
            ->withQueryString();

        $bookmarkedPostIds = $user->bookmarks()->pluck('post_id');

        $popularTags = Tag::has('posts')
            ->withCount('posts')
            ->orderByDesc('posts_count')
            ->take(20)
            ->get(['id', 'name']);

        $sourceStatuses = SourceFetchStatus::whereIn('source', $allSources)->get()
            ->keyBy('source');

        return Inertia::render('Dashboard', [
            'posts' => $posts,
            'platforms' => collect($allSources)->map(fn ($source) => [
                'key' => $source,
                'label' => ucfirst($source),
                'enabled' => in_array($source, $enabledSources, true),
                'status' => $sourceStatuses->get($source)?->status,
                'lastRunAt' => $sourceStatuses->get($source)?->last_run_at?->diffForHumans(),
                'postsFetched' => $sourceStatuses->get($source)?->posts_fetched,
            ]),
            'popularTags' => $popularTags,
            'bookmarkedPostIds' => $bookmarkedPostIds,
            'dismissedPostIds' => $filters['show_hidden'] ? $dismissedPostIds : [],
            'filters' => $filters,
            'rssUrl' => route('feed.rss', $user->rssToken()),
        ]);
    }
}
