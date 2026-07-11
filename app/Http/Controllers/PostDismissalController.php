<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostDismissal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PostDismissalController extends Controller
{
    public function store(Request $request, Post $post): RedirectResponse
    {
        PostDismissal::firstOrCreate([
            'user_id' => $request->user()->id,
            'post_id' => $post->id,
        ]);

        return back();
    }

    public function destroy(Request $request, Post $post): RedirectResponse
    {
        PostDismissal::where('user_id', $request->user()->id)
            ->where('post_id', $post->id)
            ->delete();

        return back();
    }
}
