<?php

namespace Tests\Feature;

use App\Models\StoryGroup;
use App\Services\StoryGrouper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoryGrouperTest extends TestCase
{
    use RefreshDatabase;

    public function test_exact_title_match_reuses_existing_group(): void
    {
        $grouper = new StoryGrouper();

        $firstId = $grouper->resolveGroupId('OpenAI releases GPT-5.6', 'https://a.example.com');
        $secondId = $grouper->resolveGroupId('OpenAI Releases GPT-5.6!', 'https://b.example.com');

        $this->assertSame($firstId, $secondId);
        $this->assertSame(1, StoryGroup::count());
    }

    public function test_similar_title_reuses_existing_group(): void
    {
        $grouper = new StoryGrouper();

        $firstId = $grouper->resolveGroupId(
            'EU Parliament greenlights Chat Control 1.0',
            'https://a.example.com'
        );
        $secondId = $grouper->resolveGroupId(
            'EU Parliament greenlights Chat Control 1.0 legislation',
            'https://b.example.com'
        );

        $this->assertSame($firstId, $secondId);
        $this->assertSame(1, StoryGroup::count());
    }

    public function test_unrelated_titles_create_separate_groups(): void
    {
        $grouper = new StoryGrouper();

        $firstId = $grouper->resolveGroupId('How to use Laravel queues', 'https://a.example.com');
        $secondId = $grouper->resolveGroupId('Why cats sleep so much', 'https://b.example.com');

        $this->assertNotSame($firstId, $secondId);
        $this->assertSame(2, StoryGroup::count());
    }
}
