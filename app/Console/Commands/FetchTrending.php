<?php

namespace App\Console\Commands;

use App\Jobs\FetchTrendingPosts;
use Illuminate\Console\Command;

class FetchTrending extends Command
{
    protected $signature = 'app:fetch-trending {source? : One of the keys from config/trending.php (all sources if omitted)}';

    protected $description = 'Dispatch jobs to fetch trending posts from configured platforms';

    public function handle(): int
    {
        $requested = $this->argument('source');
        $sources = $requested ? [$requested] : array_keys(config('trending.sources'));

        foreach ($sources as $source) {
            if (! array_key_exists($source, config('trending.sources'))) {
                $this->error("Unknown source: {$source}");

                continue;
            }

            FetchTrendingPosts::dispatch($source);
            $this->info("Dispatched fetch job for: {$source}");
        }

        return self::SUCCESS;
    }
}
