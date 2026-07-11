<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RootRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_visiting_root_is_redirected_to_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/')->assertRedirect(route('dashboard'));
    }

    public function test_guest_visiting_root_sees_welcome_page(): void
    {
        $this->get('/')->assertOk()->assertInertia(fn ($page) => $page->component('Welcome'));
    }
}
