<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoryGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'canonical_title',
        'canonical_url',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
