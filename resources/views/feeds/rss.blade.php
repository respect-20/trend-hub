<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<rss version="2.0">
    <channel>
        <title>{{ $title }}</title>
        <link>{{ $link }}</link>
        <description>Your personalized trending feed</description>
        <lastBuildDate>{{ now()->toRfc2822String() }}</lastBuildDate>
        @foreach ($posts as $post)
        <item>
            <title>{{ $post->title }}</title>
            <link>{{ $post->url }}</link>
            <guid isPermaLink="false">{{ $post->source }}-{{ $post->external_id }}</guid>
            <description>{{ ucfirst($post->source) }} · score {{ $post->trending_score }}@if($post->author) · by {{ $post->author }}@endif</description>
            @if($post->published_at)
            <pubDate>{{ $post->published_at->toRfc2822String() }}</pubDate>
            @endif
        </item>
        @endforeach
    </channel>
</rss>
