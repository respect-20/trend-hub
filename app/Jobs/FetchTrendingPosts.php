<?php

namespace App\Jobs;

use App\Fetchers\Exceptions\FetcherNotConfiguredException;
use App\Models\Post;
use App\Models\SourceFetchStatus;
use App\Models\Tag;
use App\Services\StoryGrouper;
use App\Services\TrendingScorer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class FetchTrendingPosts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $source)
    {
    }

    public function handle(TrendingScorer $scorer, StoryGrouper $grouper): void
    {
        $fetcherClass = config("trending.sources.{$this->source}");

        if (! $fetcherClass) {
            Log::warning('FetchTrendingPosts: unknown source', ['source' => $this->source]);

            return;
        }

        try {
            $posts = app($fetcherClass)->fetch();
        } catch (FetcherNotConfiguredException $e) {
            $this->recordStatus('not_configured', 0, $e->getMessage());

            return;
        } catch (Throwable $e) {
            $this->recordStatus('failed', 0, $e->getMessage());
            throw $e;
        }

        if (empty($posts)) {
            $this->recordStatus('failed', 0, 'Fetcher returned no posts');

            return;
        }

        $scores = $scorer->scoreBatch($posts);

        foreach ($posts as $post) {
            $existing = Post::where('source', $post->source)
                ->where('external_id', $post->externalId)
                ->first();

            $attributes = $post->toArray();
            $attributes['trending_score'] = $scores[$post->externalId] ?? 0;
            $attributes['previous_trending_score'] = $existing->trending_score ?? 0;
            $attributes['story_group_id'] = $grouper->resolveGroupId($post->title, $post->url);

            $model = Post::updateOrCreate(
                ['source' => $post->source, 'external_id' => $post->externalId],
                $attributes
            );

            $tagIds = collect($post->tags)
                ->filter()
                ->map(fn ($name) => Tag::firstOrCreate(['name' => $name])->id);

            $model->postTags()->sync($tagIds);
        }

        $this->recordStatus('success', count($posts));
    }

    private function recordStatus(string $status, int $postsFetched, ?string $errorMessage = null): void
    {
        $existingSuccessAt = SourceFetchStatus::where('source', $this->source)->value('last_success_at');

        SourceFetchStatus::updateOrCreate(
            ['source' => $this->source],
            [
                'status' => $status,
                'posts_fetched' => $postsFetched,
                'error_message' => $errorMessage,
                'last_run_at' => now(),
                'last_success_at' => $status === 'success' ? now() : $existingSuccessAt,
            ]
        );
    }
}
