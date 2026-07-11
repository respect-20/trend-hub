<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\FeedQueryBuilder;
use Illuminate\Http\Response;

class RssController extends Controller
{
    public function show(string $token, FeedQueryBuilder $feedQuery): Response
    {
        $user = User::where('rss_token', $token)->firstOrFail();

        $posts = $feedQuery->build($user, ['sort' => 'trending', 'range' => 'all'])
            ->take(50)
            ->get();

        $xml = view('feeds.rss', [
            'posts' => $posts,
            'title' => config('app.name').' — Trending',
            'link' => route('dashboard'),
        ])->render();

        return response($xml, 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8']);
    }
}
