<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'source',
        'external_id',
        'title',
        'url',
        'author',
        'thumbnail_url',
        'tags',
        'raw_engagement',
        'trending_score',
        'previous_trending_score',
        'published_at',
        'fetched_at',
        'story_group_id',
    ];

    protected $casts = [
        'tags' => 'array',
        'raw_engagement' => 'array',
        'published_at' => 'datetime',
        'fetched_at' => 'datetime',
    ];

    protected $appends = ['velocity'];

    public function storyGroup(): BelongsTo
    {
        return $this->belongsTo(StoryGroup::class);
    }

    public function postTags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'post_tag');
    }

    public function getVelocityAttribute(): int
    {
        return $this->trending_score - $this->previous_trending_score;
    }
}
