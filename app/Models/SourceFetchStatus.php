<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SourceFetchStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'source',
        'status',
        'posts_fetched',
        'error_message',
        'last_run_at',
        'last_success_at',
    ];

    protected $casts = [
        'last_run_at' => 'datetime',
        'last_success_at' => 'datetime',
    ];
}
