<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    public function store(Request $request, Post $post): RedirectResponse
    {
        Bookmark::firstOrCreate([
            'user_id' => $request->user()->id,
            'post_id' => $post->id,
        ]);

        return back();
    }

    public function destroy(Request $request, Post $post): RedirectResponse
    {
        Bookmark::where('user_id', $request->user()->id)
            ->where('post_id', $post->id)
            ->delete();

        return back();
    }
}
