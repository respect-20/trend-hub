<?php

namespace App\Fetchers;

use Carbon\Carbon;

final class NormalizedPost
{
    public function __construct(
        public readonly string $source,
        public readonly string $externalId,
        public readonly string $title,
        public readonly string $url,
        public readonly ?string $author,
        public readonly ?string $thumbnailUrl,
        public readonly array $tags,
        public readonly array $rawEngagement,
        public readonly ?Carbon $publishedAt,
    ) {
    }

    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'external_id' => $this->externalId,
            'title' => $this->title,
            'url' => $this->url,
            'author' => $this->author,
            'thumbnail_url' => $this->thumbnailUrl,
            'tags' => $this->tags,
            'raw_engagement' => $this->rawEngagement,
            'published_at' => $this->publishedAt,
            'fetched_at' => now(),
        ];
    }
}
