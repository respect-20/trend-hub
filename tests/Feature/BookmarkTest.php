<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookmarkTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_bookmark_and_unbookmark_a_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)->post(route('bookmarks.store', $post))->assertRedirect();
        $this->assertDatabaseHas('bookmarks', ['user_id' => $user->id, 'post_id' => $post->id]);

        $this->actingAs($user)->delete(route('bookmarks.destroy', $post))->assertRedirect();
        $this->assertDatabaseMissing('bookmarks', ['user_id' => $user->id, 'post_id' => $post->id]);
    }

    public function test_bookmarking_the_same_post_twice_does_not_duplicate(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)->post(route('bookmarks.store', $post));
        $this->actingAs($user)->post(route('bookmarks.store', $post));

        $this->assertSame(1, $user->bookmarks()->count());
    }
}
