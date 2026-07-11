<?php

namespace App\Console\Commands;

use App\Mail\DailyDigestMail;
use App\Models\Post;
use App\Models\User;
use App\Models\UserPlatformPreference;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDailyDigest extends Command
{
    protected $signature = 'app:send-daily-digest';

    protected $description = 'Email each opted-in user their top trending stories from the last 24 hours';

    public function handle(): int
    {
        $allSources = array_keys(config('trending.sources'));

        User::where('receive_digest', true)->chunk(50, function ($users) use ($allSources) {
            foreach ($users as $user) {
                $disabledSources = UserPlatformPreference::where('user_id', $user->id)
                    ->where('enabled', false)
                    ->pluck('source')
                    ->all();

                $enabledSources = array_values(array_diff($allSources, $disabledSources));

                $posts = Post::whereIn('source', $enabledSources)
                    ->where('published_at', '>=', now()->subDay())
                    ->orderByDesc('trending_score')
                    ->take(10)
                    ->get();

                if ($posts->isEmpty()) {
                    continue;
                }

                Mail::to($user->email)->send(new DailyDigestMail($posts));
                $this->info("Sent digest to {$user->email} ({$posts->count()} stories)");
            }
        });

        return self::SUCCESS;
    }
}
