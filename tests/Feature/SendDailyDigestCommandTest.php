<?php

namespace Tests\Feature;

use App\Mail\DailyDigestMail;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendDailyDigestCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_digest_to_opted_in_users_with_recent_posts(): void
    {
        Mail::fake();

        $optedIn = User::factory()->create(['receive_digest' => true]);
        $optedOut = User::factory()->create(['receive_digest' => false]);

        Post::factory()->create(['published_at' => now()->subHours(3), 'trending_score' => 90]);
        Post::factory()->create(['published_at' => now()->subDays(5)]);

        $this->artisan('app:send-daily-digest')->assertSuccessful();

        Mail::assertSent(DailyDigestMail::class, function ($mail) use ($optedIn) {
            return $mail->hasTo($optedIn->email) && $mail->posts->count() === 1;
        });

        Mail::assertNotSent(DailyDigestMail::class, function ($mail) use ($optedOut) {
            return $mail->hasTo($optedOut->email);
        });
    }

    public function test_skips_users_with_no_recent_posts(): void
    {
        Mail::fake();

        User::factory()->create(['receive_digest' => true]);
        Post::factory()->create(['published_at' => now()->subDays(10)]);

        $this->artisan('app:send-daily-digest')->assertSuccessful();

        Mail::assertNothingSent();
    }
}
