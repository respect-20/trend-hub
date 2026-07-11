<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('app:fetch-trending devto')->everyThirtyMinutes();
        $schedule->command('app:fetch-trending hackernews')->everyFifteenMinutes();
        $schedule->command('app:fetch-trending stackoverflow')->everyThirtyMinutes();
        $schedule->command('app:fetch-trending producthunt')->daily();
        $schedule->command('app:fetch-trending lobsters')->everyThirtyMinutes();
        $schedule->command('app:fetch-trending mastodon')->everyFifteenMinutes();
        $schedule->command('app:send-daily-digest')->dailyAt('08:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
