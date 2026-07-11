<?php

namespace App\Fetchers;

use App\Fetchers\Contracts\FetcherInterface;
use App\Fetchers\Exceptions\FetcherNotConfiguredException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProductHuntFetcher implements FetcherInterface
{
    public function source(): string
    {
        return 'producthunt';
    }

    public function fetch(): array
    {
        $token = config('trending.producthunt.token');

        if (empty($token)) {
            throw new FetcherNotConfiguredException('Set PRODUCTHUNT_TOKEN to enable this source.');
        }

        $query = <<<'GRAPHQL'
            query {
                posts(order: VOTES, first: 30) {
                    edges {
                        node {
                            id
                            name
                            tagline
                            url
                            votesCount
                            commentsCount
                            createdAt
                            thumbnail { url }
                            topics { edges { node { name } } }
                        }
                    }
                }
            }
            GRAPHQL;

        $response = Http::withToken($token)
            ->post('https://api.producthunt.com/v2/api/graphql', ['query' => $query]);

        if ($response->failed()) {
            Log::warning('ProductHuntFetcher request failed', ['status' => $response->status()]);

            return [];
        }

        $edges = $response->json('data.posts.edges', []);

        return collect($edges)->map(function (array $edge) {
            $node = $edge['node'];
            $topics = collect($node['topics']['edges'] ?? [])->pluck('node.name')->all();

            return new NormalizedPost(
                source: $this->source(),
                externalId: (string) $node['id'],
                title: $node['name'],
                url: $node['url'],
                author: null,
                thumbnailUrl: $node['thumbnail']['url'] ?? null,
                tags: $topics,
                rawEngagement: [
                    'votes' => $node['votesCount'] ?? 0,
                    'comments' => $node['commentsCount'] ?? 0,
                ],
                publishedAt: isset($node['createdAt']) ? \Carbon\Carbon::parse($node['createdAt']) : null,
            );
        })->all();
    }
}
