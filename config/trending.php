<?php

return [

    'sources' => [
        'devto' => \App\Fetchers\DevToFetcher::class,
        'hackernews' => \App\Fetchers\HackerNewsFetcher::class,
        'stackoverflow' => \App\Fetchers\StackOverflowFetcher::class,
        'producthunt' => \App\Fetchers\ProductHuntFetcher::class,
        'lobsters' => \App\Fetchers\LobstersFetcher::class,
        'mastodon' => \App\Fetchers\MastodonFetcher::class,
    ],

    'producthunt' => [
        'token' => env('PRODUCTHUNT_TOKEN'),
    ],

    'mastodon' => [
        'instance' => env('MASTODON_INSTANCE', 'mastodon.social'),
    ],

];
