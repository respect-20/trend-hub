<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_disable_a_platform_and_it_is_excluded_from_feed(): void
    {
        $user = User::factory()->create();
        Post::factory()->create(['source' => 'devto', 'title' => 'A devto post']);
        Post::factory()->create(['source' => 'hackernews', 'title' => 'A hn post']);

        $this->actingAs($user)
            ->patch(route('platform-preferences.update'), ['source' => 'devto', 'enabled' => false])
            ->assertRedirect();

        $this->assertDatabaseHas('user_platform_preferences', [
            'user_id' => $user->id,
            'source' => 'devto',
            'enabled' => false,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertInertia(fn ($page) => $page
            ->where('platforms', fn ($platforms) => collect($platforms)->firstWhere('key', 'devto')['enabled'] === false)
        );
    }

    public function test_all_platforms_enabled_by_default(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertInertia(fn ($page) => $page
            ->where('platforms', fn ($platforms) => collect($platforms)->every(fn ($p) => $p['enabled'] === true))
        );
    }
}
